<?php

namespace Studio\Util\Parsers;

/**
 * Represents an HTML element.
 */
class HTMLDocumentNode {

    public $nodetype = 3;
    public $tag = 'text';
    public $attr = array();

    /**
     * @var HTMLDocumentNode[]
     */
    public $children = array();

    /**
     * @var HTMLDocumentNode[]
     */
    public $nodes = array();

    /**
     * @var HTMLDocumentNode|null
     */
    public $parent = null;

    /**
     * @var array
     */
    public $_ = array();
    public $tag_start = 0;

    /**
     * @var HTMLDocument
     */
    private $dom = null;

    /**
     * Constructs a new `HTMLDocumentNode` instance for the given document.
     *
     * @param HTMLDocument $dom
     */
    function __construct(HTMLDocument $dom) {
        $this->dom = $dom;
        $this->dom->addNode($this);
    }

    /**
     * Clears the memory in the node.
     */
    public function __destruct() {
        $this->clear();
    }

    /**
     * Returns the outer HTML of the element.
     *
     * @return string
     */
    public function __toString() {
        return $this->getOuterHTML();
    }

    /**
     * Cleans up memory.
     */
    public function clear() {
        $this->dom = null;
        $this->nodes = null;
        $this->parent = null;
        $this->children = null;
    }

    /**
     * Returns the parent of this node. If a node is passed, it will reset the parent of the current node to that one.
     *
     * @param HTMLDocumentNode|null $parent
     */
    public function parent($parent = null) {
        if ($parent !== null) {
            $this->parent = $parent;
            $this->parent->nodes[] = $this;
            $this->parent->children[] = $this;
        }

        return $this->parent;
    }

    /**
     * Returns `true` if this node has one or more children.
     *
     * @return bool
     */
    public function hasChildren() {
        return !empty($this->children);
    }

    // returns children of node
    /**
     * Returns the children of this node. If an integer is passed, returns the node at that index if it exists. If that
     * child does not exist, returns `null`.
     *
     * @param int $idx
     * @return HTMLDocumentNode[]|HTMLDocumentNode
     */
    public function getChildNodes($idx = -1) {
        if ($idx === -1) {
            return $this->children;
        }

        if (isset($this->children[$idx])) {
            return $this->children[$idx];
        }

        return null;
    }

    /**
     * Returns the first child of the node if it has one, or `null`.
     *
     * @return HTMLDocumentNode|null
     */
    public function getFirstChild() {
        if (count($this->children) > 0) {
            return $this->children[0];
        }

        return null;
    }

    /**
     * Returns the last child of the node, or `null`.
     *
     * @return HTMLDocumentNode|null
     */
    public function getLastChild() {
        if (($count = count($this->children)) > 0) {
            return $this->children[$count - 1];
        }

        return null;
    }

    /**
     * Returns the next sibling of the node, or `null`.
     *
     * @return HTMLDocumentNode|null
     */
    public function getNextSibling() {
        if ($this->parent === null) {
            return null;
        }

        $idx = 0;
        $count = count($this->parent->children);

        while ($idx < $count && $this !== $this->parent->children[$idx]) {
            ++$idx;
        }

        if (++$idx >= $count) {
            return null;
        }

        return $this->parent->children[$idx];
    }

    /**
     * Returns the previous sibling of the node, or `null`.
     *
     * @return HTMLDocumentNode|null
     */
    public function getPreviousSibling() {
        if ($this->parent === null) {
            return null;
        }

        $idx = 0;
        $count = count($this->parent->children);

        while ($idx<$count && $this !== $this->parent->children[$idx]) {
            ++$idx;
        }

        if (--$idx < 0) {
            return null;
        }

        return $this->parent->children[$idx];
    }

    /**
     * Locates the specified ancestor tag in the path to the root.
     *
     * @param string $tag
     */
    protected function findAncestorTag($tag) {
        // Start by including ourselves in the comparison.
        $returnDom = $this;

        while (!is_null($returnDom)) {
            if ($returnDom->tag == $tag) {
                break;
            }

            $returnDom = $returnDom->parent;
        }

        return $returnDom;
    }

    /**
     * Returns the node's inner HTML code.
     *
     * @return string
     */
    public function getInnerHTML() {
        if (isset($this->_[5])) return $this->_[5];
        if (isset($this->_[4])) return $this->dom->restoreNoise($this->_[4]);

        $ret = '';

        foreach ($this->nodes as $n) {
            $ret .= $n->getOuterHTML();
        }

        return $ret;
    }

    /**
     * Returns the node's outer text (the tag itself and all text within).
     *
     * @return string
     */
    public function getOuterHTML() {
        if ($this->tag === 'root') return $this->getInnerHTML();

        if (isset($this->_[6])) return $this->_[6];
        if (isset($this->_[4])) return $this->dom->restoreNoise($this->_[4]);

        // Render begin tag
        if ($this->dom && $this->dom->getNodes()[$this->_[0]]) {
            $ret = $this->dom->getNodes()[$this->_[0]]->makeUp();
        }
        else {
            $ret = "";
        }

        // Render inner text
        if (isset($this->_[5])) {
            // If it's a br tag...  don't return the HDOM_INNER_INFO that we may or may not have added.
            if ($this->tag != 'br') {
                $ret .= $this->_[5];
            }
        }
        else {
            if ($this->nodes) {
                foreach ($this->nodes as $n) {
                    $ret .= $this->convertText($n->getOuterHTML());
                }
            }
        }

        // Render end tag
        if (isset($this->_[1]) && $this->_[1] != 0) {
            $ret .= '</'.$this->tag.'>';
        }

        return $ret;
    }

    /**
     * Returns the node's inner plain text.
     *
     * @return string
     */
    public function getPlainText() {
        if (isset($this->_[5])) {
            return $this->_[5];
        }

        switch ($this->nodetype) {
            case 3: return $this->dom->restoreNoise($this->_[4]);
            case 2: return '';
            case 6: return '';
        }

        if (strcasecmp($this->tag, 'script') === 0) return '';
        if (strcasecmp($this->tag, 'style') === 0) return '';

        $ret = '';

        if (!is_null($this->nodes)) {
            foreach ($this->nodes as $n) {
                $ret .= trim($this->convertText($n->getPlainText())) . ' ';
            }

            // If this node is a span... add a space at the end of it so multiple spans don't run into each other
            // This is plaintext after all
            if ($this->tag == 'span') {
                $ret .= ' ';
            }
        }

        return $ret;
    }

    /**
     * Returns a script's inner xml text, excluding the surrounding CDATA declaration.
     *
     * @return string
     */
    public function getXmlText() {
        $ret = $this->getInnerHTML();
        $ret = str_ireplace('<![CDATA[', '', $ret);
        $ret = str_replace(']]>', '', $ret);

        return $ret;
    }

    /**
     * Builds the node's text with its tag.
     *
     * @return string
     */
    protected function makeUp() {
        // Text, comment, unknown
        if (isset($this->_[4])) {
            return $this->dom->restoreNoise($this->_[4]);
        }

        $ret = '<' . $this->tag;
        $i = -1;

        foreach ($this->attr as $key => $val) {
            ++$i;

            // Skip removed attribute
            if ($val === null || $val === false) continue;

            $ret .= $this->_[3][$i][0];

            // No value attr: nowrap, checked selected...
            if ($val === true) {
                $ret .= $key;
            }
            else {
                switch ($this->_[2][$i]) {
                    case 0: $quote = '"'; break;
                    case 1: $quote = '\''; break;
                    default: $quote = '';
                }
                $ret .= $key.$this->_[3][$i][1] . '=' . $this->_[3][$i][2] . $quote . $val . $quote;
            }
        }

        $ret = $this->dom->restoreNoise($ret);
        return $ret . $this->_[7] . '>';
    }

    /**
     * Finds elements by the given CSS selector. If `$idx` is `null`, returns an array of all matching elements.
     * Otherwise, returns the nth element in the array, or `null`.
     *
     * @param string $selector
     * @param int|null $idx
     * @param bool $lowercase
     *
     * @return HTMLDocumentNode[]|HTMLDocumentNode|null
     */
    public function find($selector, $idx = null, $lowercase = false) {
        $found_keys = array();
        $selectors = $this->parseSelector($selector);

        if (($count = count($selectors)) === 0) {
            return array();
        }

        // Find each selector
        for ($c = 0; $c < $count; ++$c) {
            if (($level = count($selectors[$c])) === 0) return array();
            if (!isset($this->_[0])) return array();

            $head = array($this->_[0] => 1);

            // Handle descendant selectors, no recursive!
            for ($l = 0; $l < $level; ++$l) {
                $ret = array();

                foreach ($head as $k => $v) {
                    $n = ($k === -1) ? $this->dom->getRootNode() : $this->dom->getNodes()[$k];
                    $n->seek($selectors[$c][$l], $ret, $lowercase);
                }

                $head = $ret;
            }

            foreach ($head as $k => $v) {
                if (!isset($found_keys[$k])) {
                    $found_keys[$k] = 1;
                }
            }
        }

        // Sort keys
        ksort($found_keys);

        $found = array();
        foreach ($found_keys as $k => $v) {
            $found[] = $this->dom->getNodes()[$k];
        }

        // Return nth-element or array
        if (is_null($idx)) return $found;
        else if ($idx < 0) $idx = count($found) + $idx;
        return (isset($found[$idx])) ? $found[$idx] : null;
    }

    /**
     * Seek for the given conditions.
     *
     * @param string $selector
     * @param array $ret
     * @param bool $lowercase
     */
    protected function seek($selector, &$ret, $lowercase = false) {
        list($tag, $key, $val, $exp, $no_key) = $selector;

        // Xpath index
        if ($tag && $key && is_numeric($key)) {
            $count = 0;

            foreach ($this->children as $c) {
                if ($tag === '*' || $tag === $c->tag) {
                    if (++$count == $key) {
                        $ret[$c->_[0]] = 1;
                        return;
                    }
                }
            }

            return;
        }

        $end = (!empty($this->_[1])) ? $this->_[1] : 0;

        if ($end == 0) {
            $parent = $this->parent;

            while (!isset($parent->_[1]) && $parent !== null) {
                $end -= 1;
                $parent = $parent->parent;
            }

            $end += $parent->_[1];
        }

        for ($i = $this->_[0] + 1; $i < $end; ++$i) {
            $node = $this->dom->getNodes()[$i];
            $pass = true;

            if ($tag === '*' && !$key) {
                if (in_array($node, $this->children, true)) $ret[$i] = 1;
                continue;
            }

            // Compare tag
            if ($tag && $tag != $node->tag && $tag !== '*') $pass = false;

            // Compare key
            if ($pass && $key) {
                if ($no_key) {
                    if (isset($node->attr[$key])) $pass = false;
                }
                else {
                    if (($key != "plaintext") && !isset($node->attr[$key])) $pass = false;
                }
            }

            // Compare value
            if ($pass && $key && $val && $val !== '*') {
                // If they have told us that this is a "plaintext" search then we want the plaintext of the node - right?
                if ($key == 'plaintext') {
                    // $node->plaintext actually returns $node->getPlainText();
                    $nodeKeyValue = $node->getPlainText();
                }
                else {
                    // this is a normal search, we want the value of that attribute of the tag.
                    $nodeKeyValue = $node->attr[$key];
                }

                // Handle lowercase search
                if ($lowercase) {
                    $check = $this->match($exp, strtolower($val), strtolower($nodeKeyValue));
                }
                else {
                    $check = $this->match($exp, $val, $nodeKeyValue);
                }

                // Handle multiple class
                if (!$check && strcasecmp($key, 'class') === 0) {
                    foreach (explode(' ', $node->attr[$key]) as $k) {
                        // Without this, there were cases where leading, trailing, or double spaces lead to our comparing blanks - bad form.
                        if (!empty($k)) {
                            if ($lowercase) {
                                $check = $this->match($exp, strtolower($val), strtolower($k));
                            }
                            else {
                                $check = $this->match($exp, $val, $k);
                            }

                            if ($check) break;
                        }
                    }
                }

                if (!$check) $pass = false;
            }

            if ($pass) $ret[$i] = 1;
            unset($node);
        }
    }

    /**
     * Matches the value against the given expression.
     *
     * @param string $exp
     * @param string $pattern
     * @param string $value
     * @return string|false
     */
    protected function match($exp, $pattern, $value) {
        switch ($exp) {
            case '=':
                return ($value === $pattern);
            case '!=':
                return ($value !== $pattern);
            case '^=':
                return preg_match("/^" . preg_quote($pattern, '/') . "/", $value);
            case '$=':
                return preg_match("/" . preg_quote($pattern, '/') . "$/", $value);
            case '*=':
                if ($pattern[0] == '/') {
                    return preg_match($pattern, $value);
                }
                return preg_match("/" . $pattern . "/i", $value);
        }

        return false;
    }

    /**
     * Parses the given selector string.
     *
     * @param string $selectorString
     * @return array
     */
    protected function parseSelector($selectorString) {
        $pattern = "/([\w:\*-]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w:-]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        preg_match_all($pattern, trim($selectorString).' ', $matches, PREG_SET_ORDER);

        $selectors = array();
        $result = array();

        foreach ($matches as $m) {
            $m[0] = trim($m[0]);
            if ($m[0] === '' || $m[0] === '/' || $m[0] === '//') continue;

            list($tag, $key, $val, $exp, $no_key) = array($m[1], null, null, '=', false);
            if (!empty($m[2])) { $key = 'id'; $val = $m[2]; }
            if (!empty($m[3])) { $key = 'class'; $val = $m[3]; }
            if (!empty($m[4])) { $key = $m[4]; }
            if (!empty($m[5])) { $exp = $m[5]; }
            if (!empty($m[6])) { $val = $m[6]; }

            // Convert to lowercase
            $tag = !is_null($tag) ? strtolower($tag) : '';
            $key = !is_null($key) ? strtolower($key) : '';

            // Elements that do NOT have the specified attribute
            if (isset($key[0]) && $key[0] === '!') {
                $key = substr($key, 1);
                $no_key = true;
            }

            $result[] = array($tag, $key, $val, $exp, $no_key);

            if (trim($m[7]) === ',') {
                $selectors[] = $result;
                $result = array();
            }
        }

        if (count($result) > 0) {
            $selectors[] = $result;
        }

        return $selectors;
    }

    /**
     * Converts the text from one character set to another if the two sets are not the same.
     *
     * @param string $text
     * @return string
     */
    public function convertText($text) {
        $convertedText = $text;
        $sourceCharset = '';
        $targetCharset = '';

        if ($this->dom) {
            $sourceCharset = strtoupper($this->dom->getCharset());
            $targetCharset = strtoupper($this->dom->getTargetCharset());
        }

        if (!empty($sourceCharset) && !empty($targetCharset) && (strcasecmp($sourceCharset, $targetCharset) != 0)) {
            // Check if the reported encoding could have been incorrect and the text is actually already UTF-8
            if ((strcasecmp($targetCharset, 'UTF-8') == 0) && $this->isUtf8($text)) {
                $convertedText = $text;
            }
            else {
                $convertedText = iconv($sourceCharset, $targetCharset, $text);
            }
        }

        // Lets make sure that we don't have that silly BOM issue with any of the utf-8 text we output.
        if ($targetCharset == 'UTF-8') {
            if (substr($convertedText, 0, 3) == "\xef\xbb\xbf") {
                $convertedText = substr($convertedText, 3);
            }

            if (substr($convertedText, -3) == "\xef\xbb\xbf") {
                $convertedText = substr($convertedText, 0, -3);
            }
        }

        return $convertedText;
    }

    /**
    * Returns true if `$string` is valid UTF-8 by checking the bits per character.
    *
    * @param string $str
    * @return boolean
    */
    protected static function isUtf8($str) {
        $c = 0;
        $b = 0;
        $bits = 0;
        $len = strlen($str);

        for ($i=0; $i < $len; $i++) {
            $c = ord($str[$i]);

            if ($c > 128) {
                if(($c >= 254)) return false;
                elseif($c >= 252) $bits=6;
                elseif($c >= 248) $bits=5;
                elseif($c >= 240) $bits=4;
                elseif($c >= 224) $bits=3;
                elseif($c >= 192) $bits=2;
                else return false;

                if (($i + $bits) > $len) return false;

                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }

        return true;
    }

    /**
     * Function to try a few tricks to determine the displayed size of an img on the page.
     * NOTE: This will ONLY work on an IMG tag. Returns FALSE on all other tag types.
     *
     * @author John Schlick
     * @version April 19 2012
     * @return array an array containing the 'height' and 'width' of the image on the page or -1 if we can't figure it out.
     */
    public function getDisplaySize() {
        $width = -1;
        $height = -1;

        if ($this->tag !== 'img') {
            return false;
        }

        // See if there is aheight or width attribute in the tag itself.
        if (isset($this->attr['width'])) {
            $width = $this->attr['width'];
        }

        if (isset($this->attr['height'])) {
            $height = $this->attr['height'];
        }

        // Now look for an inline style.
        if (isset($this->attr['style'])) {
            // Thanks to user gnarf from stackoverflow for this regular expression.
            $attributes = array();
            preg_match_all("/([\w-]+)\s*:\s*([^;]+)\s*;?/", $this->attr['style'], $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }

            // If there is a width in the style attributes:
            if (isset($attributes['width']) && $width == -1) {
                // check that the last two characters are px (pixels)
                if (strtolower(substr($attributes['width'], -2)) == 'px') {
                    $proposed_width = substr($attributes['width'], 0, -2);
                    // Now make sure that it's an integer and not something stupid.
                    if (filter_var($proposed_width, FILTER_VALIDATE_INT)) {
                        $width = $proposed_width;
                    }
                }
            }

            // If there is a width in the style attributes:
            if (isset($attributes['height']) && $height == -1) {
                // check that the last two characters are px (pixels)
                if (strtolower(substr($attributes['height'], -2)) == 'px') {
                    $proposed_height = substr($attributes['height'], 0, -2);

                    // Now make sure that it's an integer and not something stupid.
                    if (filter_var($proposed_height, FILTER_VALIDATE_INT)) {
                        $height = $proposed_height;
                    }
                }
            }
        }

        return array('height' => $height, 'width' => $width);
    }

    /**
     * Returns all attributes in the node.
     *
     * @return array
     */
    public function getAllAttributes() {
        return $this->attr;
    }

    /**
     * Returns the value of the specified attribute.
     *
     * @return string|bool
     */
    public function getAttribute($name) {
        if (isset($this->attr[$name])) {
            return $this->convertText($this->attr[$name]);
        }

        return array_key_exists($name, $this->attr);
    }

    /**
     * Sets the value of the attribute.
     *
     * @param string $name
     * @param string $value
     */
    public function setAttribute($name, $value) {
        if (!isset($this->attr[$name])) {
            $this->_[3][] = array(' ', '', '');
            $this->_[2][] = 0;
        }

        $this->attr[$name] = $value;
    }

    /**
     * Returns `true` if the node has the specified attribute.
     *
     * @param string $name
     * @return bool
     */
    public function hasAttribute($name) {
        return (array_key_exists($name, $this->attr)) ? true : isset($this->attr[$name]);
    }

    /**
     * Removes the specified attribute from the node.
     *
     * @param string $name
     */
    public function removeAttribute($name) {
        $this->setAttribute($name, null);
    }

    /**
     * Finds the first child element with a matching ID.
     *
     * @param string $id
     * @return HTMLDocumentNode|null
     */
    public function getElementById($id) {
        return $this->find("#$id", 0);
    }

    /**
     * Returns an array of child elements with a matching ID. If `$idx` is passed, then the element at that index will
     * be returned, or `null`.
     *
     * @param string $id
     * @param int $idx
     * @return HTMLDocumentNode[]|HTMLDocumentNode|null
     */
    public function getElementsById($id, $idx = null) {
        return $this->find("#$id", $idx);
    }

    /**
     * Returns the first element with a matching tag name.
     *
     * @param string $name
     * @return HTMLDocumentNode|null
     */
    public function getElementByTagName($name) {
        return $this->find($name, 0);
    }

    /**
    * Returns an array of child elements with a matching tag name. If `$idx` is passed, then the element at that index
    * will be returned, or `null`.
    *
    * @param string $name
    * @param int $idx
    * @return HTMLDocumentNode[]|HTMLDocumentNode|null
    */
    public function getElementsByTagName($name, $idx = null) {
        return $this->find($name, $idx);
    }

    /**
     * Returns the tag name of this node.
     *
     * @return string
     */
    public function getTagName() {
        return $this->tag;
    }

    /**
     * Appends a child to this node and returns the added node.
     *
     * @param HTMLDocumentNode $node
     * @return HTMLDocumentNode
     */
    function appendChild(HTMLDocumentNode $node) {
        $node->parent($this);
        return $node;
    }

    /**
     * Returns the full class string of the node.
     *
     * @return string
     */
    public function getClassName() {
        if (!$this->hasAttribute('class')) {
            return '';
        }

        return $this->getAttribute('class');
    }

    /**
     * Sets the full class string of the node.
     *
     * @param string $className
     */
    public function setClassName($className) {
        $this->setAttribute('class', $className);
    }

    /**
     * Returns `true` if the element has the given class name in its class list.
     *
     * @param string $className
     * @param bool $caseSensitive
     * @return bool
     */
    public function hasClass($className, $caseSensitive = true) {
        $classList = preg_split('/\s+/', trim($this->getClassName()));

        if (!$caseSensitive) {
            $className = strtolower($className);
            $classList = array_map('strtolower', $classList);
        }

        return in_array($className, $classList);
    }

    /**
     * Sets the outer HTML of the node.
     *
     * @param string $html
     */
    public function setOuterHTML($html) {
        return $this->_[6] = $html;
    }

    /**
     * Sets the inner HTML of the node.
     *
     * @param string $html
     */
    public function setInnerHTML($html) {
        if (isset($this->_[4])) {
            return $this->_[4] = $html;
        }

        return $this->_[5] = $html;
    }

}
