<?php

use PHPUnit\Framework\TestCase;

// Mock CBasicRecordFile class as it's not provided
class CBasicRecordFile {
    function createFile($filename, $size) { return true; }
    function open() { return true; }
}

// Define constants used in CBookList
if (!defined('SP')) { define('SP',"\r"); }
if (!defined('SP1')) { define('SP1',"\x07"); }

require_once __DIR__ . '/../src/bin/class_book_list.php';

class BookListTest extends TestCase
{
    public function testSetAndGetOptions()
    {
        $bookList = new CBookList();

        $options = [
            'timesft' => '1',
            'perpage' => '10',
            'notify'  => '1',
            'showdlg' => '0',
            'useicon' => '1',
            'numicon' => '20',
            'css'     => 'default.css',
            'btn'     => 'classic.btn'
        ];

        $bookList->setOptions($options);

        $retrievedOptions = [];
        $bookList->getOptions($retrievedOptions);

        $this->assertEquals($options['timesft'], $retrievedOptions['timesft']);
        $this->assertEquals($options['perpage'], $retrievedOptions['perpage']);
        $this->assertEquals($options['notify'], $retrievedOptions['notify']);
        $this->assertEquals($options['showdlg'], $retrievedOptions['showdlg']);
        $this->assertEquals($options['useicon'], $retrievedOptions['useicon']);
        $this->assertEquals($options['numicon'], $retrievedOptions['numicon']);
        $this->assertEquals($options['css'], $retrievedOptions['css']);
        $this->assertEquals($options['btn'], $retrievedOptions['btn']);
    }

    public function testGetOptionsWithEmptyValues()
    {
        $bookList = new CBookList();

        // Directly set _opts to simulate empty values
        // In a real scenario, you might use reflection or a test-specific method
        // to set protected properties for testing.
        $bookList->_opts = ['', '', '', '', '', '', '', ''];

        $retrievedOptions = [];
        $bookList->getOptions($retrievedOptions);

        $this->assertArrayNotHasKey('timesft', $retrievedOptions);
        $this->assertArrayNotHasKey('perpage', $retrievedOptions);
        $this->assertArrayNotHasKey('notify', $retrievedOptions);
        $this->assertArrayNotHasKey('showdlg', $retrievedOptions);
        $this->assertArrayNotHasKey('useicon', $retrievedOptions);
        $this->assertArrayNotHasKey('numicon', $retrievedOptions);
        $this->assertArrayNotHasKey('css', $retrievedOptions);
        $this->assertArrayNotHasKey('btn', $retrievedOptions);
    }
}
