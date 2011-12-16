<?php
/**
 * Copyright (c) 2011 Simon Coulton
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
 * THE SOFTWARE.
 * 
 * @author      Simon Coulton <@simoncoulton>
 * @category    Xml
 * @package     XmlElement
 */

/**
 * @namespace
 */
namespace Xml;

use \SimpleXMLElement;
use \SimpleXMLIterator;

/**
 * Wrapper for SimpleXML to provide a few more convenience methods mainly 
 * dealing with appending/removing nodes from an Xml document. Xml can be added
 * as either a XmlElement, SimpleXMLElement, or a plain string.
 *
 * @category    Gowi
 * @category    Xml
 * @package     XmlElement
 */
class XmlElement extends SimpleXMLIterator
{
    /**
     * Convenience method for clearing errors
     */
    public function clearErrors()
    {
        libxml_clear_errors();
    }
    
    /**
     * Convenience method for retrieving errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return libxml_get_errors();
    }
    
    /**
     * Convenience method for the last error.
     * 
     * @return array
     */
    public function getLastError()
    {
        return libxml_get_load_error();
    }
    
    /**
     * Convenience method for setting use internal errors. Clears all previous
     * errors.
     * 
     * @param bool $errors
     * @return bool
     */
    public function useInternalErrors($errors = false)
    {
        $this->clearErrors();
        
        return libxml_use_internal_errors($errors);
    }
    
    /**
     * Loads a string or file resource of structured xml data. This should be
     * used over the constructor method to ensure that all returned elements
     * are of type XmlElement.
     * 
     * @param string $xml
     * @param bool $useErrors
     * @return XmlElement 
     */
    public static function load($xml, $useErrors = false)
    {
        $class = get_class();
        if ('.xml' === substr($xml, -4)) {
            $xml = simplexml_load_file($xml, $class);
        } else {
            $xml = simplexml_load_string($xml, $class);
        }
        $xml->useInternalErrors($useErrors);
        
        return $xml;
    }
    
    /**
     * Add a CDATA value to the current node.
     * 
     * @param string $value 
     * @return XmlElement
     */
    public function addCDATA($value)
    {
        $dom = dom_import_simplexml($this);
        $owner = $dom->ownerDocument;
        $dom->appendChild($owner->createCDATASection($value));
        
        return $this;
    }
    
    /**
     * Add a child node filled with CDATA to the current node.
     * 
     * @param string $name
     * @param string $value
     * @return XmlElement 
     */
    public function addChildCDATA($name, $value)
    {
        $node = $this->addChild($name);
        $node->addCDATA($value);
        
        return $this;
    }
    
    /**
     * Append a string, XmlElement or SimpleXMLElement to the current node.
     * 
     * @param string|XmlElement|SimpleXMLElement $xml 
     * @return XmlElement
     */
    public function append($xml)
    {
        if ($xml instanceof SimpleXMLElement) {
            $xml = $xml->asXML();
        }
        if (!($xml instanceof XmlElement)) {
            $xml = self::load($xml);
        }
        $nodeName = $xml->getName();
        $nodeValue = trim((string)$xml);
        if ('' === $nodeValue) {
            $node = $this->addChild($nodeName);
            foreach ($xml->children() as $child) {
                $node->append($child);
            }
        } else {
            $node = $this->addChild($nodeName, $nodeValue);
        }
        foreach ($xml->attributes() as $attr=>$value) {
            $node->addAttribute($attr, $value);
        }
        
        return $node;
    }
    
    /**
     * Remove the current node from the xml, or remove a node based on xpath.
     * 
     * @param string $xpath 
     * @return XmlElement
     */
    public function removeNode($xpath = null)
    {
        if (null !== $xpath) {
            foreach ($this->xpath($xpath) as $node) {
                $this->_removeNode($node);
            }
        } else {
            $this->_removeNode($this);
        }
        
        return $this;
    }
    
    /**
     * Remove a node from the xml.
     * 
     * @param XmlElement $node
     * @return XmlElement
     */
    protected function _removeNode($node)
    {
        $dom = dom_import_simplexml($node);
        
        return $dom->parentNode->removeChild($dom);
    }
    
    /**
     * Convenience method to return the nth node matching an xpath query.
     * 
     * @param string $xpath
     * @param int $n
     * @return XmlElement 
     */
    public function xpathn($xpath, $n = 0)
    {
        $xpath = $this->xpath($xpath);
        
        return isset($xpath[$n]) ? $xpath[$n] : null;
    }
    
    /**
     * Converts an XmlElement into an array. If no node is specified, will use the
     * current node.
     * @param XmlElement $node
     * @param array $arr
     * @param string $attributeKey
     * @return array
     * 
     * @todo Manage attributes
     */
    public function toArray($node = null)
    {
        if (null === $node) {
            $node = $this;
        }
        $arr = array();
        foreach ($node->children() as $child) {
            $nodeName = $child->getName();
            $nodeValue = (string)$child;
            if (!isset($arr[$nodeName])) {
                $arr[$nodeName] = array();
            }
            foreach ($child->attributes() as $attr=>$value) {
                $arr[$nodeName][$attr] = (string)$value;
            }
            $arr[$nodeName] = $child->toArray();
            if ($child->hasChildren()) {
                $arr[$nodeName][] = $child->toArray();
            }
        }
        
        return $arr;
    }
}