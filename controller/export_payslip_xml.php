<?php
require_once('../model/database.php');
require_once('../model/session.php');

requireLogin();

$user = getCurrentUser();
$employee_id = $user['id'];

$stmt = $pdo->prepare("SELECT p.*, CONCAT(e.firstname, ' ', e.lastname) AS employee_name, r.role_name FROM payroll p JOIN employees e ON p.employee_id = e.id JOIN roles r ON e.role_id = r.id WHERE p.employee_id = ? ORDER BY p.month_year DESC LIMIT 1");
$stmt->execute([$employee_id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payroll) {
    header('Location: payslip.php?error=No payroll record found');
    exit();
}

$stmt = $pdo->prepare("SELECT COUNT(CASE WHEN status = 'present' THEN 1 END) AS days_worked, COUNT(CASE WHEN status = 'absent' THEN 1 END) AS days_absent FROM attendance WHERE employee_id = ?");
$stmt->execute([$employee_id]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;
$root = $doc->createElement('payslip');
$doc->appendChild($root);

$employeeNode = $doc->createElement('employee');
$employeeNode->appendChild($doc->createElement('name', $payroll['employee_name']));
$employeeNode->appendChild($doc->createElement('role', $payroll['role_name']));
$employeeNode->appendChild($doc->createElement('month', date('F Y', strtotime($payroll['month_year']))));
$root->appendChild($employeeNode);

$attendanceNode = $doc->createElement('attendance');
$attendanceNode->appendChild($doc->createElement('days_worked', $attendance['days_worked'] ?? 0));
$attendanceNode->appendChild($doc->createElement('days_absent', $attendance['days_absent'] ?? 0));
$attendanceNode->appendChild($doc->createElement('paid_leaves', $payroll['paid_leaves'] ?? 0));
$attendanceNode->appendChild($doc->createElement('unpaid_leaves', $payroll['unpaid_leaves'] ?? 0));
$root->appendChild($attendanceNode);

$computationNode = $doc->createElement('computation');
$computationNode->appendChild($doc->createElement('basic_salary', $payroll['basic_salary'] ?? 0));
$computationNode->appendChild($doc->createElement('total_incentives', $payroll['total_incentives'] ?? 0));
$computationNode->appendChild($doc->createElement('gross_pay', $payroll['gross_pay'] ?? 0));
$computationNode->appendChild($doc->createElement('total_deductions', $payroll['total_deductions'] ?? 0));
$computationNode->appendChild($doc->createElement('net_pay', $payroll['net_pay'] ?? 0));
$root->appendChild($computationNode);

header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="payslip.xml"');
echo $doc->saveXML();

