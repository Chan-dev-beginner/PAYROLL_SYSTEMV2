<?php
require_once('../model/database.php');
require_once('../model/session.php');

requireHR();

$stmt = $pdo->query("SELECT e.*, r.role_name, r.monthly_salary, d.department_name, s.shift_name FROM employees e JOIN roles r ON e.role_id = r.id LEFT JOIN departments d ON e.department_id = d.id LEFT JOIN shifts s ON e.shift_id = s.id ORDER BY e.created_at DESC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;
$root = $doc->createElement('employees');
$doc->appendChild($root);

foreach ($employees as $emp) {
    $employeeNode = $doc->createElement('employee');

    $fields = [
        'employee_db_id' => $emp['id'],
        'employee_id' => $emp['employee_id'],
        'firstname' => $emp['firstname'],
        'lastname' => $emp['lastname'],
        'email' => $emp['email'],
        'phone' => $emp['phone'],
        'role' => $emp['role_name'],
        'monthly_salary' => $emp['monthly_salary'],
        'department' => $emp['department_name'],
        'shift' => $emp['shift_name'],
        'hire_date' => $emp['hire_date'],
        'status' => $emp['status'],
        'created_at' => $emp['created_at'],
    ];

    foreach ($fields as $tag => $value) {
        $child = $doc->createElement($tag);
        $child->appendChild($doc->createTextNode($value ?? ''));
        $employeeNode->appendChild($child);
    }

    $root->appendChild($employeeNode);
}

header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="employees.xml"');
echo $doc->saveXML();

