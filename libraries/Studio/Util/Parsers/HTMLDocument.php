<?php

namespace Studio\Util\Parsers;

/**
 * Parses an HTML document into a DOM.
 *
 * @property string $outerHTML The entire document as an HTML string.
 * @property string $innerHTML The entire document as an HTML string, excluding the root element.
 * @property string $plainText All text in the document stripped of tags.
 * @property string $charset The current character set being used by the document.
 * @property string $targetCharset The target character set.
 */
class HTMLDocument {

    /**
     * @var HTMLDocumentNode|null
     */
    protected $root = null;

    /**
     * @var HTMLDocumentNode[]
     */
    protected $nodes = array();

    /**
     * Whether or not this document is on lowercase mode.
     *
     * @var bool
     */
    protected $lowercase = false;

    /**
     * The original size of the document in bytes.
     *
     * @var int
     */
    protected $original_size;

    /**
     * The current size of the document in bytes.
     *
     * @var int
     */
    protected $size;

    protected $pos;
    protected $doc;
    protected $char;
    protected $cursor;
    protected $parent;
    protected $noise = array();
    protected $token_blank = " \t\r\n";
    protected $token_equal = ' =/>';
    protected $token_slash = " />\r\n\t";
    protected $token_attr = ' >';
    protected $_charset = '';
    protected $_target_charset = 'UTF-8';
    protected $default_br_text = '';
    protected $default_span_text = ' ';
    protected $parseTime = 0;

    protected $self_closing_tags = array('img' => 1, 'br' => 1, 'input' => 1, 'meta' => 1, 'link' => 1, 'hr' => 1, 'base' => 1, 'embed' => 1, 'spacer' => 1);
    protected $block_tags = array('root' => 1, 'body' => 1, 'form' => 1, 'div' => 1, 'span' => 1, 'table' => 1);

    protected $optional_closing_tags = array(
        'tr' => array('tr' => 1, 'td' => 1, 'th' => 1),
        'th' => array('th' => 1),
        'td' => array('td' => 1),
        'li' => array('li' => 1),
        'dt' => array('dt' => 1, 'dd' => 1),
        'dd' => array('dd' => 1, 'dt' => 1),
        'dl' => array('dd' => 1, 'dt' => 1),
        'p' => array('p' => 1),
        'nobr' => array('nobr' => 1),
        'b' => array('b' => 1),
        'option' => array('option' => 1)
    );

    public function __construct($html) {
        // Start timing
        $startTime = microtime(true);

        // Remove zero-width space
        $html = preg_replace('/[\x{200B}-\x{200D}]/u', '', $html);

        // Prepare for parsing
        $this->prepare($html);

        // Remove noisy elements
        $this->removeNoise("'<!\[CDATA\[(.*?)\]\]>'is", true);
        $this->removeNoise("'<!--(.*?)-->'is");
        $this->removeNoise("'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is");
        $this->removeNoise("'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is");
        $this->removeNoise("'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is");
        $this->removeNoise("'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is");
        $this->removeNoise("'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is");
        $this->removeNoise("'(<\?)(.*?)(\?>)'s", true);
        $this->removeNoise("'(\{\w)(.*?)(\})'s", true);

        // Parse html
        while ($this->parse());
        $this->root->_[1] = $this->cursor;

        // Detect charset
        $this->detectCharset();

        // Save parse time
        $this->parseTime = (microtime(true) - $startTime);
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
        return $this->root->find($selector, $idx, $lowercase);
    }

    /**
     * Clears the memory of the document.
     */
    public function clear() {
        foreach ($this->nodes as $n) {
            $n->clear();
            $n = null;
        }

        if (isset($this->children)) {
            foreach ($this->children as $n) {
                $n->clear();
                $n = null;
            }
        }

        if (isset($this->parent)) {
            $this->parent->clear();
            unset($this->parent);
        }

        if (isset($this->root)) {
            $this->root->clear();
            unset($this->root);
        }

        unset($this->doc);
        unset($this->noise);
    }

    public function __destruct() {
        $this->clear();
    }

    protected function prepare($string) {
        $this->clear();
        $this->original_size = strlen($string);

        // Strip out newline and carriage-return characters
        $str = str_replace(["\r\n", "\r", "\n"], ' ', $string);
        $this->size = strlen($string);

        // Set attributes
        $this->doc = $str;
        $this->pos = 0;
        $this->cursor = 1;
        $this->noise = array();
        $this->nodes = array();
        $this->lowercase = true;
        $this->default_br_text = "\r\n";
        $this->default_span_text = ' ';
        $this->root = new HTMLDocumentNode($this);
        $this->root->tag = 'root';
        $this->root->_[0] = -1;
        $this->root->nodetype = 5;
        $this->parent = $this->root;

        if ($this->size > 0) {
            $this->char = $this->doc[0];
        }
    }

    /**
     * Removes a noisy element from the document, but preserves it in memory for later restoration.
     *
     * @param string $pattern
     * @param bool $removeTag
     */
    public function removeNoise($pattern, $removeTag = false) {
        $count = preg_match_all($pattern, $this->doc, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        for ($i = $count - 1; $i > -1; --$i) {
            $key = '___noise___' . sprintf('% 5d', count($this->noise) + 1000);
            $idx = ($removeTag) ? 0 : 1;
            $this->noise[$key] = $matches[$i][$idx][0];
            $this->doc = substr_replace($this->doc, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
        }

        // Reset the length of content
        $this->size = strlen($this->doc);

        // Update the char index
        if ($this->size > 0) {
            $this->char = $this->doc[0];
        }
    }

    /**
     * Restores noise from the noise-tag and returns the text.
     *
     * @param string $text
     * @return string
     */
    public function restoreNoise($text) {
        while (($pos = strpos($text, '___noise___')) !== false) {
            if (strlen($text) > $pos + 15) {
                $key = '___noise___' . $text[$pos+11] . $text[$pos+12] . $text[$pos+13] . $text[$pos+14] . $text[$pos+15];

                if (isset($this->noise[$key])) {
                    $text = substr($text, 0, $pos) . $this->noise[$key] . substr($text, $pos + 16);
                }
                else {
                    // do this to prevent an infinite loop.
                    $text = substr($text, 0, $pos) . 'UNDEFINED NOISE FOR KEY: ' . $key . substr($text, $pos + 16);
                }
            }
            else {
                $text = substr($text, 0, $pos) . 'NO NUMERIC NOISE KEY' . substr($text, $pos + 11);
            }
        }

        return $text;
    }

    protected function parse() {
        if (($s = $this->copyUntilChar('<')) === '') {
            return $this->readTag();
        }

        $node = new HTMLDocumentNode($this);
        ++$this->cursor;
        $node->_[4] = $s;
        $this->linkNodes($node, false);

        return true;
    }

    protected function readTag() {
        if ($this->char !== '<') {
            $this->root->_[1] = $this->cursor;
            return false;
        }

        $begin_tag_pos = $this->pos;
        $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null;

        // End tag
        if ($this->char === '/') {
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
            $this->skip($this->token_blank);
            $tag = $this->copyUntilChar('>');

            // Skip attributes in end tag
            if (($pos = strpos($tag, ' ')) !== false) {
                $tag = substr($tag, 0, $pos);
            }

            $parent_lower = strtolower($this->parent->tag);
            $tag_lower = strtolower($tag);

            if ($parent_lower !== $tag_lower) {
                if (isset($this->optional_closing_tags[$parent_lower]) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[1] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $this->parent->parent;
                    }

                    if (strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $org_parent;
                        if ($this->parent->parent) $this->parent = $this->parent->parent;
                        $this->parent->_[1] = $this->cursor;
                        return $this->asTextNode($tag);
                    }
                }
                else if (($this->parent->parent) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[1] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $this->parent->parent;
                    }

                    if (strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $org_parent; // restore origonal parent
                        $this->parent->_[1] = $this->cursor;
                        return $this->asTextNode($tag);
                    }
                }
                else if (($this->parent->parent) && strtolower($this->parent->parent->tag) === $tag_lower) {
                    $this->parent->_[1] = 0;
                    $this->parent = $this->parent->parent;
                }
                else {
                    return $this->asTextNode($tag);
                }
            }

            $this->parent->_[1] = $this->cursor;
            if ($this->parent->parent) $this->parent = $this->parent->parent;

            $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        $node = new HTMLDocumentNode($this);
        $node->_[0] = $this->cursor;
        ++$this->cursor;
        $tag = $this->copyUntil($this->token_slash);
        $node->tag_start = $begin_tag_pos;

        // Doctype, cdata & comments...
        if (isset($tag[0]) && $tag[0] === '!') {
            $node->_[4] = '<' . $tag . $this->copyUntilChar('>');

            if (isset($tag[2]) && $tag[1] === '-' && $tag[2] === '-') {
                $node->nodetype = 2;
                $node->tag = 'comment';
            }
            else {
                $node->nodetype = 6;
                $node->tag = 'unknown';
            }

            if ($this->char === '>') $node->_[4].='>';
            $this->linkNodes($node, true);
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;

            return true;
        }

        // Text
        if ($pos = strpos($tag, '<') !== false) {
            $tag = '<' . substr($tag, 0, -1);
            $node->_[4] = $tag;
            $this->linkNodes($node, false);
            $this->char = $this->doc[--$this->pos];
            return true;
        }

        if (!preg_match("/^[\w:-]+$/", $tag)) {
            $node->_[4] = '<' . $tag . $this->copyUntil('<>');
            if ($this->char === '<') {
                $this->linkNodes($node, false);
                return true;
            }

            if ($this->char === '>') $node->_[4] .= '>';
            $this->linkNodes($node, false);
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;

            return true;
        }

        // Begin tag
        $node->nodetype = 1;
        $tag_lower = strtolower($tag);
        $node->tag = ($this->lowercase) ? $tag_lower : $tag;

        // handle optional closing tags
        if (isset($this->optional_closing_tags[$tag_lower])) {
            while (isset($this->optional_closing_tags[$tag_lower][strtolower($this->parent->tag)])) {
                $this->parent->_[1] = 0;
                $this->parent = $this->parent->parent;
            }
            $node->parent = $this->parent;
        }

        $guard = 0;
        $space = array($this->copySkip($this->token_blank), '', '');

        // Attributes
        do {
            if ($this->char !== null && $space[0] === '') {
                break;
            }

            $name = $this->copyUntil($this->token_equal);

            if ($guard === $this->pos) {
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                continue;
            }

            $guard = $this->pos;

            // Handle endless '<'
            if ($this->pos >= $this->size-1 && $this->char !== '>') {
                $node->nodetype = 3;
                $node->_[1] = 0;
                $node->_[4] = '<' . $tag . $space[0] . $name;
                $node->tag = 'text';
                $this->linkNodes($node, false);
                return true;
            }

            // handle mismatch '<'
            if ($this->doc[$this->pos - 1] == '<') {
                $node->nodetype = 3;
                $node->tag = 'text';
                $node->attr = array();
                $node->_[1] = 0;
                $node->_[4] = substr($this->doc, $begin_tag_pos, $this->pos - $begin_tag_pos - 1);
                $this->pos -= 2;
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                $this->linkNodes($node, false);
                return true;
            }

            if ($name !== '/' && $name !== '') {
                $space[1] = $this->copySkip($this->token_blank);
                $name = $this->restoreNoise($name);

                if ($this->lowercase) $name = strtolower($name);

                if ($this->char === '=') {
                    $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                    $this->parseAttr($node, $name, $space);
                }
                else {
                    //no value attr: nowrap, checked selected...
                    $node->_[2][] = 3;
                    $node->attr[$name] = true;
                    if ($this->char != '>') $this->char = $this->doc[--$this->pos]; // prev
                }

                $node->_[3][] = $space;
                $space = array($this->copySkip($this->token_blank), '', '');
            }
            else break;
        }
        while ($this->char !== '>' && $this->char !== '/');

        $this->linkNodes($node, true);
        $node->_[7] = $space[0];

        // check self closing
        if ($this->copyUntilCharEscape('>') === '/') {
            $node->_[7] .= '/';
            $node->_[1] = 0;
        }
        else {
            // Reset parent
            if (!isset($this->self_closing_tags[strtolower($node->tag)])) {
                $this->parent = $node;
            }
        }

        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;

        // If it's a BR tag, we need to set it's text to the default text.
        // This way when we see it in plaintext, we can generate formatting that the user wants.
        // Since a br tag never has sub nodes, this works well.
        if ($node->tag == 'br') {
            $node->_[5] = $this->default_br_text;
        }

        return true;
    }

    /**
     * @param HTMLDocumentNode $node
     * @param string $name
     * @param array $space
     */
    protected function parseAttr($node, $name, &$space) {
        // Per sourceforge: http://sourceforge.net/tracker/?func=detail&aid=3061408&group_id=218559&atid=1044037
        // If the attribute is already defined inside a tag, only pay attention to the first one as opposed to the last one
        if (isset($node->attr[$name])) {
            return;
        }

        $space[2] = $this->copySkip($this->token_blank);

        switch ($this->char) {
            case '"':
                $node->_[2][] = 0;
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                $node->attr[$name] = $this->restoreNoise($this->copyUntilCharEscape('"'));
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                break;
            case '\'':
                $node->_[2][] = 1;
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                $node->attr[$name] = $this->restoreNoise($this->copyUntilCharEscape('\''));
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                break;
            default:
                $node->_[2][] = 3;
                $node->attr[$name] = $this->restoreNoise($this->copyUntil($this->token_attr));
        }

        // PaperG: Attributes should not have \r or \n in them, that counts as html whitespace.
        $node->attr[$name] = str_replace("\r", "", $node->attr[$name]);
        $node->attr[$name] = str_replace("\n", "", $node->attr[$name]);

        // PaperG: If this is a "class" selector, lets get rid of the preceeding and trailing space since some people leave it in the multi class case.
        if ($name == "class") {
            $node->attr[$name] = trim($node->attr[$name]);
        }
    }

    /**
     * @param HTMLDocumentNode $node
     * @param bool $isChild
     */
    protected function linkNodes(&$node, $isChild) {
        $node->parent = $this->parent;
        $this->parent->nodes[] = $node;

        if ($isChild) {
            $this->parent->children[] = $node;
        }
    }

    /**
     * @param string $tag
     * @return bool
     */
    protected function asTextNode($tag) {
        $node = new HTMLDocumentNode($this);
        ++$this->cursor;
        $node->_[4] = '</' . $tag . '>';
        $this->linkNodes($node, false);
        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
        return true;
    }

    /**
     * @param string $chars
     */
    protected function skip($chars) {
        $this->pos += strspn($this->doc, $chars, $this->pos);
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null;
    }

    /**
     * @param string $chars
     * @return string
     */
    protected function copySkip($chars) {
        $pos = $this->pos;
        $len = strspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null;

        if ($len === 0) return '';
        return substr($this->doc, $pos, $len);
    }

    /**
     * @param string $chars
     * @return string
     */
    protected function copyUntil($chars) {
        $pos = $this->pos;
        $len = strcspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null;

        return substr($this->doc, $pos, $len);
    }

    /**
     * @param string $char
     * @return string
     */
    protected function copyUntilChar($char) {
        if ($this->char === null) return '';

        if (($pos = strpos($this->doc, $char, $this->pos)) === false) {
            $ret = substr($this->doc, $this->pos, $this->size - $this->pos);
            $this->char = null;
            $this->pos = $this->size;
            return $ret;
        }

        if ($pos === $this->pos) return '';

        $pos_old = $this->pos;
        $this->char = $this->doc[$pos];
        $this->pos = $pos;

        return substr($this->doc, $pos_old, $pos - $pos_old);
    }

    /**
     * @param string $char
     * @return string
     */
    protected function copyUntilCharEscape($char) {
        if ($this->char === null) return '';

        $start = $this->pos;

        while (true) {
            if (($pos = strpos($this->doc, $char, $start)) === false) {
                $ret = substr($this->doc, $this->pos, $this->size - $this->pos);
                $this->char = null;
                $this->pos = $this->size;
                return $ret;
            }

            if ($pos === $this->pos) return '';

            if ($this->doc[$pos - 1] === '\\') {
                $start = $pos + 1;
                continue;
            }

            $pos_old = $this->pos;
            $this->char = $this->doc[$pos];
            $this->pos = $pos;

            return substr($this->doc, $pos_old, $pos - $pos_old);
        }
    }

    /**
     * @param string $text
     * @return array
     */
    protected function searchNoise($text) {
        foreach ($this->noise as $noiseElement) {
            if (strpos($noiseElement, $text) !== false) {
                return $noiseElement;
            }
        }
    }

    /**
     * Detects, saves, and returns the character set to use for this document.
     *
     * @return string
     */
    protected function detectCharset() {
        $charset = null;

        // Find a content-type meta tag
        if (!empty($el = $this->root->find('meta[http-equiv=Content-Type]', 0, true))) {
            $fullvalue = $el->getAttribute('content');

            if (!empty($fullvalue))  {
                $success = preg_match('/charset=(.+)/i', $fullvalue, $matches);
                if ($success) {
                    $charset = $matches[1];
                }
                else {
                    // If there is a meta tag, and they don't specify the character set, research says that it's typically ISO-8859-1
                    $charset = 'ISO-8859-1';
                }
            }
        }

        // Find a charset meta tag
        if (empty($charset)) {
            if (!empty($el = $this->root->find('meta[charset]', 0, true))) {
                $charset = strtoupper($el->getAttribute('charset'));

                if ($charset === 'UTF8') $charset = 'UTF-8';
            }
        }

		// If we couldn't find a charset above, then lets try to detect one based on the text we got...
		if (empty($charset)) {
			// Use this in case mb_detect_charset isn't installed/loaded on this machine.
            $charset = false;

			if (function_exists('mb_detect_encoding')) {
				// Have php try to detect the encoding from the text given to us.
				$charset = mb_detect_encoding($this->root->getPlainText() . "ascii", array("UTF-8", "CP1252"));
			}

			// and if this doesn't work...  then we need to just wrongheadedly assume it's UTF-8 so that we can move on - cause this will usually give us most of what we need...
			if ($charset === false) {
				$charset = 'UTF-8';
			}
		}

		// Since CP1252 is a superset, if we get one of it's subsets, we want it instead.
		if ((strtolower($charset) == strtolower('ISO-8859-1')) || (strtolower($charset) == strtolower('Latin1')) || (strtolower($charset) == strtolower('Latin-1'))) {
			$charset = 'CP1252';
		}

		return $this->_charset = $charset;
    }

    /**
     * Returns the document as an HTML string.
     *
     * @return string
     */
    public function __toString() {
        return $this->root->getInnerHTML();
    }

    /**
     * Returns an array of nodes in this document.
     *
     * @return HTMLDocumentNode[]
     */
    public function getNodes() {
        return $this->nodes;
    }

    /**
     * Returns the root node in this document.
     *
     * @return HTMLDocumentNode|null
     */
    public function getRootNode() {
        return $this->root;
    }

    /**
     * Returns an array of nodes which are children of the root node. If `$idx` is provided, returns the child at that
     * index, or `null`.
     *
     * @param int $idx
     * @return HTMLDocumentNode[]|HTMLDocumentNode|null
     */
    public function getChildNodes($idx = -1) {
        return $this->root->getChildNodes($idx);
    }

    /**
     * Returns the first child node in the document.
     *
     * @return HTMLDocumentNode|null
     */
    public function getFirstChild() {
        return $this->root->getFirstChild();
    }

    /**
     * Returns the last child node in the document.
     *
     * @return HTMLDocumentNode|null
     */
    public function getLastChild() {
        return $this->root->getLastChild();
    }

    /**
     * Creates an element with the given tag and inner HTML.
     *
     * @param string $tag
     * @param string $innerHTML
     * @return HTMLDocumentNode
     */
    public function createElement($tag, $innerHTML = '') {
        return @(new HTMLDocument("<$tag>$innerHTML</$tag>"))->getFirstChild();
    }

    /**
     * Creates a text node with the given value.
     *
     * @param string $value
     * @return HTMLDocumentNode
     */
    public function createTextNode($value) {
        return @end((new HTMLDocument($value))->getNodes());
    }

    /**
     * Finds the first element with a matching ID.
     *
     * @param string $id
     * @return HTMLDocumentNode|null
     */
    public function getElementById($id) {
        return $this->find("#$id", 0);
    }

    /**
     * Returns an array of elements with a matching ID. If `$idx` is passed, then the element at that index will
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
    * Returns an array of elements with a matching tag name. If `$idx` is passed, then the element at that index
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
     * Adds the given node to the document.
     *
     * @param HTMLDocumentNode $node
     * @internal
     */
    public function addNode(HTMLDocumentNode $node) {
        $this->nodes[] = $node;
    }

    /**
     * Returns the character set this document is using.
     *
     * @return string
     */
    public function getCharset() {
        return $this->_charset;
    }

    /**
     * Returns the character set this document would like to use.
     *
     * @return string
     */
    public function getTargetCharset() {
        return 'UTF-8';
    }

    /**
     * Returns the number of seconds it took to parse this document.
     *
     * @return double
     */
    public function getParseTime() {
        return $this->parseTime;
    }

    /**
     * Returns the entire document as an HTML string.
     *
     * @return string
     */
    public function getHTML() {
        return $this->root->getOuterHTML();
    }

    /**
     * Returns the text in the document stripped of all tags.
     *
     * @return string
     */
    public function getPlainText() {
        return $this->root->getPlainText();
    }

}
