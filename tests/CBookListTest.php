<?php

use PHPUnit\Framework\TestCase;

final class CBookListTest extends TestCase
{
    private $originalSetupContent;
    private $tempDataPath;
    private $setupFilePath = __DIR__ . '/../src/setup.php';
    private $bookListClassPath = __DIR__ . '/../src/bin/class_book_list.php';
    private $basicRecordFileClassPath = __DIR__ . '/../src/bin/class_basic_record_file.php';
    private $bookListFilePath; // Path to the actual book.lst file

    protected function setUp(): void
    {
        // Save original setup.php content
        $this->originalSetupContent = file_get_contents($this->setupFilePath);

        // Create a temporary directory for test data
        $this->tempDataPath = sys_get_temp_dir() . '/zgb_test_data_' . uniqid();
        if (!mkdir($this->tempDataPath, 0777, true)) {
            $this->fail("Failed to create temporary directory: " . $this->tempDataPath);
        }

        // Define the full path to the book.lst file within the temp directory
        $this->bookListFilePath = $this->tempDataPath . '/book.lst';

        // Include necessary classes
        require_once $this->basicRecordFileClassPath;
        require_once $this->bookListClassPath;
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->tempDataPath)) {
            $files = glob($this->tempDataPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDataPath);
        }
    }

    public function testWriteAndReadBookList(): void
    {
        // Create a new CBookList instance
        $bookList = new CBookList();

        // Create the book list file
        $this->assertTrue($bookList->create($this->bookListFilePath), "Failed to create book list file.");

        // Open the book list file
        $this->assertTrue($bookList->open($this->bookListFilePath), "Failed to open book list file.");

        // Set properties for a new book entry
        $expectedName = 'test_book_name';
        $expectedTitle = 'Test Book Title';
        $expectedPass = 'test_password';
        $expectedEmail = 'test@example.com';
        $expectedUrl = 'http://test.com';
        $expectedUrlName = 'Test Site';
        $expectedHtmlT = '<html><head><title>Test</title></head><body>';
        $expectedHtmlB = '</body></html>';
        $expectedDesc = 'This is a test description.';
        $expectedRegTime = '2025-08-19 10:00:00';

        $bookList->name = $expectedName;
        $bookList->title = $expectedTitle;
        $bookList->pass = $expectedPass;
        $bookList->email = $expectedEmail;
        $bookList->url = $expectedUrl;
        $bookList->urlname = $expectedUrlName;
        $bookList->htmlt = $expectedHtmlT;
        $bookList->htmlb = $expectedHtmlB;
        $bookList->desc = $expectedDesc;
        $bookList->regtime = $expectedRegTime;

        // Append a new record
        $this->assertTrue($bookList->appendNew() > 0, "Failed to append new record.");

        // Update (write) the record
        $this->assertTrue($bookList->update(), "Failed to update record.");

        // Close the book list file
        $this->assertTrue($bookList->close(), "Failed to close book list file.");

        // Re-open the book list to read the data
        $bookListRead = new CBookList();
        $this->assertTrue($bookListRead->open($this->bookListFilePath), "Failed to re-open book list file for reading.");

        // Find the newly added book by name
        $this->assertTrue($bookListRead->find($expectedName), "Failed to find the added book.");

        // Assert that the read properties match the written properties
        $this->assertEquals($expectedName, $bookListRead->name);
        $this->assertEquals($expectedTitle, $bookListRead->title);
        $this->assertEquals($expectedPass, $bookListRead->pass);
        $this->assertEquals($expectedEmail, $bookListRead->email);
        $this->assertEquals($expectedUrl, $bookListRead->url);
        $this->assertEquals($expectedUrlName, $bookListRead->urlname);
        $this->assertEquals($expectedHtmlT, $bookListRead->htmlt);
        $this->assertEquals($expectedHtmlB, $bookListRead->htmlb);
        $this->assertEquals($expectedDesc, $bookListRead->desc);
        $this->assertEquals($expectedRegTime, $bookListRead->regtime);

        // Test getRecordCount
        $this->assertEquals(1, $bookListRead->getRecordCount(), "Record count is incorrect.");

        // Close the book list file
        $this->assertTrue($bookListRead->close(), "Failed to close book list file after reading.");
    }
}