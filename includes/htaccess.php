<?php

require_once ABSPATH . '/wp-admin/includes/misc.php';

class Htaccess {

    private $__path;
    private $__header = array(
        '<Files "*">',
        'Order deny,allow'
    );
    private $__footer = array(
        '</Files>'
    );

    /**
     * Initializes $__path.
     * 
     * @param string $dir
     */
    public function setPath($dir) {
        $this->__path = $dir . '/.htaccess';
    }

    /**
     * Checks if .htaccess file is found, readable and writeable.
     * 
     * @return array
     */
    public function checkRequirements() {
        $status = array(
            'found' => false,
            'readable' => false,
            'writeable' => false
        );

        if (file_exists($this->__path)) { //File found
            $status['found'] = true;
        }
        if (is_readable($this->__path)) { //File readable
            $status['readable'] = true;
        }
        if (is_writeable($this->__path)) { //File writeable
            $status['writeable'] = true;
        }

        return $status;
    }

    /**
     * Returs array of denied IP addresses from .htaccess.
     * 
     * @return array
     */
    public function getDeniedIPs() {
        return $this->__getLines('deny from ');
    }

    /**
     * Adds 'deny from $IP' to .htaccess.
     * 
     * @param string $IP
     * @return boolean
     */
    public function denyIP($IP) {
        if (!filter_var($IP, FILTER_VALIDATE_IP)) {
            return false;
        }

        return $this->__addLine('deny from ' . $IP);
    }

    /**
     * Removes 'deny from $IP' from .htaccess.
     * 
     * @param string $IP
     * @return boolean
     */
    public function undenyIP($IP) {
        if (!filter_var($IP, FILTER_VALIDATE_IP)) {
            return false;
        }

        return $this->__removeLine('deny from ' . $IP);
    }

    /**
     * Edits ErrorDocument 403 line in .htaccess.
     * 
     * @param string $message
     * @return boolean
     */
    public function edit403Message($message) {
        if (empty($message)) {
            return $this->remove403Message();
        }

        $line = 'ErrorDocument 403 "' . $message . '"';

        $otherLines = $this->__getLines('ErrorDocument 403 ', true, true);

        $insertion = array_merge($this->__header, array($line), $otherLines, $this->__footer);

        return insert_with_markers($this->__path, 'Brute Force Login Protection', $insertion);
    }

    /**
     * Removes ErrorDocument 403 line from .htaccess.
     * 
     * @return boolean
     */
    public function remove403Message() {
        return $this->__removeLine('', 'ErrorDocument 403 ');
    }

    /**
     * Comments out all BFLP lines in .htaccess.
     * 
     * @return boolean
     */
    public function commentLines() {
        $currentLines = $this->__getLines();

        $insertion = array();
        foreach ($currentLines as $line) {
            $insertion[] = '#' . $line;
        }

        return insert_with_markers($this->__path, 'Brute Force Login Protection', $insertion);
    }

    /**
     * Uncomments all commented BFLP lines in .htaccess.
     * 
     * @return boolean
     */
    public function uncommentLines() {
        $currentLines = $this->__getLines(false, false);

        $lines = array();
        foreach ($currentLines as $line) {
            if (substr($line, 0, 1) === '#') {
                $lines[] = substr($line, 1);
            }
        }

        $insertion = array_merge($this->__header, $lines, $this->__footer);

        return insert_with_markers($this->__path, 'Brute Force Login Protection', $insertion);
    }

    /**
     * Private functions
     */

    /**
     * Returs array of (prefixed) lines from .htaccess.
     * 
     * @param string $prefix
     * @return array
     */
    private function __getLines($prefix = false, $onlyBody = true, $exceptPrefix = false) {
        $allLines = extract_from_markers($this->__path, 'Brute Force Login Protection');

        if ($onlyBody) {
            $allLines = array_diff($allLines, $this->__header, $this->__footer);
        }

        if (!$prefix) {
            return $allLines;
        }

        $prefixLength = strlen($prefix);

        $prefixedLines = array();
        foreach ($allLines as $line) {
            if ($exceptPrefix) {
                if (substr($line, 0, $prefixLength) !== $prefix) {
                    $prefixedLines[] = $line;
                }
            } elseif (substr($line, 0, $prefixLength) === $prefix) {
                $prefixedLines[] = substr($line, $prefixLength);
            }
        }

        return $prefixedLines;
    }

    /**
     * Adds single line to .htaccess.
     * 
     * @param string $line
     * @return boolean
     */
    private function __addLine($line) {
        $insertion = array_merge($this->__header, $this->__getLines(), array($line), $this->__footer);

        return insert_with_markers($this->__path, 'Brute Force Login Protection', array_unique($insertion));
    }

    /**
     * Removes single line from .htaccess.
     * 
     * @param string $line
     * @param string $prefix
     * @return boolean
     */
    private function __removeLine($line, $prefix = false) {
        $insertion = $this->__getLines(false, false);

        if ($prefix !== false) {
            $lineKey = false;
            $prefixLength = strlen($prefix);
            foreach ($insertion as $key => $line) {
                if (substr($line, 0, $prefixLength) === $prefix) {
                    $lineKey = $key;
                    break;
                }
            }
        } else {
            $lineKey = array_search($line, $insertion);
        }

        if ($lineKey === false) {
            return true;
        }

        unset($insertion[$lineKey]);

        return insert_with_markers($this->__path, 'Brute Force Login Protection', $insertion);
    }

}
