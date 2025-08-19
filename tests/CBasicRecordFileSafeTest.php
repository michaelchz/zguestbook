<?php

use PHPUnit\Framework\TestCase;

// Include the class under test
require_once __DIR__ . '/../src/bin/class_basic_record_file_safe.php';

class CBasicRecordFileSafeTest extends TestCase
{
    private $testFilePath;
    private $recordSize = 100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFilePath = __DIR__ . '/test_basic_record_file_safe.dat';
        // Ensure the file does not exist before each test
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up the test file after each test
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        parent::tearDown();
    }

    public function testCreateFile()
    {
        $file = new CBasicRecordFile();
        $this->assertTrue($file->createFile($this->testFilePath, $this->recordSize));
        $this->assertFileExists($this->testFilePath);

        // Verify file size (HEADER_SIZE + recordSize)
        $expectedSize = 8 * 8 + $this->recordSize; // HEADER_SIZE + 1 initial record
        $this->assertEquals($expectedSize, filesize($this->testFilePath));

        // Verify header content (magic number)
        $fp = fopen($this->testFilePath, 'rb');
        fseek($fp, 0);
        $header = fread($fp, 4); // Read magic number
        fclose($fp);
        $this->assertEquals('ZC10', $header);
    }

    public function testOpenAndClose()
    {
        $file = new CBasicRecordFile();
        $file->createFile($this->testFilePath, $this->recordSize);

        $this->assertTrue($file->open($this->testFilePath));
        $file->close();
    }

    public function testAppendNewRecord()
    {
        $file = new CBasicRecordFile();
        $this->assertTrue($file->createFile($this->testFilePath, $this->recordSize));
        $this->assertFileExists($this->testFilePath);

        // Verify file size (HEADER_SIZE + recordSize)
        $expectedSize = 8 * 8 + $this->recordSize; // HEADER_SIZE + 1 initial record
        $this->assertEquals($expectedSize, filesize($this->testFilePath));

        // Verify header content (magic number)
        $fp = fopen($this->testFilePath, 'rb');
        fseek($fp, 0);
        $header = fread($fp, 4); // Read magic number
        fclose($fp);
        $this->assertEquals('ZC10', $header);

        $file->open($this->testFilePath);

        $initialRecordCount = $file->getRecordCount();
        $this->assertEquals(0, $initialRecordCount);

        $recordId = $file->appendNew();
        $this->assertIsInt($recordId);
        $this->assertGreaterThan(0, $recordId);
        $this->assertEquals(1, $file->getRecordCount());

        $file->close();
    }

    public function testSetAndGetAbsolutePosition()
    {
        $file = new CBasicRecordFile();
        $file->createFile($this->testFilePath, $this->recordSize);
        $file->open($this->testFilePath);

        $recordId1 = $file->appendNew();
        $recordId2 = $file->appendNew();

        // Set position to the first record
        $this->assertTrue($file->setAbsolutePosition($recordId1));
        $this->assertEquals($recordId1, $file->getAbsolutePosition());

        // Set position to the second record
        $this->assertTrue($file->setAbsolutePosition($recordId2));
        $this->assertEquals($recordId2, $file->getAbsolutePosition());

        // Try to set position to an invalid record ID
        $this->assertFalse($file->setAbsolutePosition(999));

        $file->close();
    }

    public function testUpdateRecord()
    {
        $file = new CBasicRecordFile();
        $file->createFile($this->testFilePath, $this->recordSize);
        $file->open($this->testFilePath);

        $recordId = $file->appendNew();
        $this->assertTrue($file->setAbsolutePosition($recordId));

        $originalContent = $file->recordBuffer;
        $this->assertStringContainsString('XXXXXXXXXXXXXYYY', $originalContent);

        $newContent = 'Updated record content for testing.';
        $file->recordBuffer = $newContent;
        $this->assertTrue($file->update());

        // Re-open file to ensure changes are persisted
        $file->close();
        $file->open($this->testFilePath);
        $this->assertTrue($file->setAbsolutePosition($recordId));
        $this->assertEquals($newContent, trim($file->recordBuffer));

        $file->close();
    }

    public function testDeleteRecord()
    {
        $file = new CBasicRecordFile();
        $file->createFile($this->testFilePath, $this->recordSize);
        $file->open($this->testFilePath);

        $recordId1 = $file->appendNew();
        $recordId2 = $file->appendNew();
        $recordId3 = $file->appendNew();

        $this->assertEquals(3, $file->getRecordCount());

        // Delete the middle record
        $this->assertTrue($file->setAbsolutePosition($recordId2));
        $this->assertTrue($file->delete());
        $this->assertEquals(2, $file->getRecordCount());

        // Verify remaining records are accessible
        $this->assertTrue($file->setAbsolutePosition($recordId1));
        $this->assertTrue($file->setAbsolutePosition($recordId3));

        // Try to access deleted record
        $this->assertFalse($file->setAbsolutePosition($recordId2));

        $file->close();
    }

    public function testReadAndWriteMemo()
    {
        $file = new CBasicRecordFile();
        $file->createFile($this->testFilePath, $this->recordSize);
        $file->open($this->testFilePath);

        $memoContent = 'This is a test memo content.';
        $this->assertGreaterThan(0, $file->writeMemo($memoContent));

        $readMemo = $file->readMemo();
        $this->assertStringStartsWith($memoContent, $readMemo);

        $file->close();
    }

    public function testMemoPersistenceAndOverwrite()
    {
        $file = new CBasicRecordFile();
        $file->createFile($this->testFilePath, $this->recordSize);
        $file->open($this->testFilePath);

        $initialMemo = 'Initial memo content for persistence test.';
        $this->assertGreaterThan(0, $file->writeMemo($initialMemo));
        $file->close();

        // Re-open and verify persistence
        $file = new CBasicRecordFile();
        $this->assertTrue($file->open($this->testFilePath));
        $readMemo = $file->readMemo();
        $this->assertStringStartsWith($initialMemo, $readMemo);
        $file->close();

        // Re-open, overwrite, and verify
        $file = new CBasicRecordFile();
        $this->assertTrue($file->open($this->testFilePath));
        $newMemo = 'This is the new memo content, overwriting the old one.';
        $this->assertGreaterThan(0, $file->writeMemo($newMemo));
        $file->close();

        // Re-open and verify the new memo persists
        $file = new CBasicRecordFile();
        $this->assertTrue($file->open($this->testFilePath));
        $readNewMemo = $file->readMemo();
        $this->assertStringStartsWith($newMemo, $readNewMemo);
        $file->close();
    }

    public function testDestroyFile()
    {
        $file = new CBasicRecordFile();
        $file->createFile($this->testFilePath, $this->recordSize);
        $this->assertFileExists($this->testFilePath);

        $this->assertTrue($file->destroyFile($this->testFilePath));
        $this->assertFileDoesNotExist($this->testFilePath);
    }

    public function testWriteAndReadMultipleRecords()
    {
        $file = new CBasicRecordFile();
        $this->assertTrue($file->createFile($this->testFilePath, $this->recordSize));
        $this->assertTrue($file->open($this->testFilePath));

        $predefinedRecords = [
            'First static record.',
            'Second static record with some more text.',
            'Third record, short and sweet.',
            'Fourth record, this one is a bit longer to test record size handling.',
            'Fifth and final record.',
        ];

        $writtenRecords = $predefinedRecords;
        $numRecords = count($predefinedRecords);

        foreach ($predefinedRecords as $recordContent) {
            $recordId = $file->appendNew();
            $this->assertIsInt($recordId);
            $this->assertGreaterThan(0, $recordId);

            $this->assertTrue($file->setAbsolutePosition($recordId));
            $file->recordBuffer = $recordContent;
            $this->assertTrue($file->update());
        }

        $this->assertEquals($numRecords, $file->getRecordCount());
        $file->close();

        // Re-open the file to read records
        $file = new CBasicRecordFile();
        $this->assertTrue($file->open($this->testFilePath));

        $readRecords = [];
        $this->assertTrue($file->moveFirst());
        do {
            $readRecords[] = trim($file->recordBuffer);
        } while ($file->moveNext());

        $this->assertEquals($writtenRecords, $readRecords);

        $file->close();
    }

    public function testRandomReadRecords()
    {
        $file = new CBasicRecordFile();
        $this->assertTrue($file->createFile($this->testFilePath, $this->recordSize));
        $this->assertTrue($file->open($this->testFilePath));

        $predefinedRecords = [];
        $recordIds = [];
        for ($i = 0; $i < 10; $i++) {
            $content = "Record number " . ($i + 1) . " - This is some test content for random access.";
            $predefinedRecords[$i] = $content;

            $recordId = $file->appendNew();
            $this->assertIsInt($recordId);
            $this->assertGreaterThan(0, $recordId);
            $recordIds[$i] = $recordId;

            $this->assertTrue($file->setAbsolutePosition($recordId));
            $file->recordBuffer = $content;
            $this->assertTrue($file->update());
        }

        $this->assertEquals(count($predefinedRecords), $file->getRecordCount());
        $file->close();

        // Re-open the file to read records randomly
        $file = new CBasicRecordFile();
        $this->assertTrue($file->open($this->testFilePath));

        // Create a map from recordId to its original content
        $recordIdToContentMap = [];
        for ($i = 0; $i < 10; $i++) {
            $recordIdToContentMap[$recordIds[$i]] = $predefinedRecords[$i];
        }

        // Shuffle the record IDs for random access
        shuffle($recordIds);

        foreach ($recordIds as $recordId) {
            $this->assertTrue($file->setAbsolutePosition($recordId), "Failed to set position to record ID: " . $recordId);
            $expectedContent = $recordIdToContentMap[$recordId];
            $this->assertEquals($expectedContent, trim($file->recordBuffer), "Content mismatch for record ID: " . $recordId);
        }

        $file->close();
    }
}
