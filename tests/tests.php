<?php

require_once __DIR__ . '/testframework.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$testFramework = new TestFramework();

function testDbConnection() {
    global $config;
    try {
        $db = new Database($config["db"]["path"]);
        return assertExpression($db !== null, "Database connection successful", "Failed to connect to database");
    } catch (Exception $e) {
        return assertExpression(false, "", "Database connection error: " . $e->getMessage());
    }
}

function testDbCount() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $count = $db->Count("page");
    return assertExpression($count === 3, "Table count is correct", "Table count is incorrect");
}

function testDbCreate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = ['title' => 'Test Page', 'content' => 'Test Content'];
    $id = $db->Create("page", $data);
    return assertExpression($id > 0, "Record created successfully", "Failed to create record");
}

function testDbRead() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = $db->Read("page", 1);
    return assertExpression($data['title'] === 'Page 1', "Record read successfully", "Failed to read record");
}

function testDbUpdate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = ['title' => 'Updated Page', 'content' => 'Updated Content'];
    $result = $db->Update("page", 1, $data);
    $updated = $db->Read("page", 1);
    return assertExpression($updated['title'] === 'Updated Page', "Record updated successfully", "Failed to update record");
}

function testDbDelete() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $result = $db->Delete("page", 2);
    $data = $db->Read("page", 2);
    return assertExpression($data === false, "Record deleted successfully", "Failed to delete record");
}

function testPageRender() {
    $page = new Page(__DIR__ . '/../site/templates/index.tpl');
    $data = ['title' => 'Test', 'content' => 'Content'];
    $output = $page->Render($data);
    return assertExpression(strpos($output, 'Test') !== false, "Page rendered successfully", "Failed to render page");
}

$testFramework->add('Database connection', 'testDbConnection');
$testFramework->add('Table count', 'testDbCount');
$testFramework->add('Data create', 'testDbCreate');
$testFramework->add('Data read', 'testDbRead');
$testFramework->add('Data update', 'testDbUpdate');
$testFramework->add('Data delete', 'testDbDelete');
$testFramework->add('Page render', 'testPageRender');

$testFramework->run();
echo $testFramework->getResult();