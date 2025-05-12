<?php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = ['success' => false, 'message' => ''];
    try {
        // Validate required fields
        $required = ['fullName', 'course', 'phone', 'email', 'district'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Validate files
        if (empty($_FILES['academicDoc']['tmp_name'])) {
            throw new Exception("Academic document is required.");
        }
        
        if (empty($_FILES['paymentProof']['tmp_name'])) {
            throw new Exception("Payment proof is required.");
        }

        $name = $_POST['fullName'];
        $course = $_POST['course']; 
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $district = $_POST['district'];

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filenames to prevent overwrites
        $academicExt = pathinfo($_FILES['academicDoc']['name'], PATHINFO_EXTENSION);
        $paymentExt = pathinfo($_FILES['paymentProof']['name'], PATHINFO_EXTENSION);
        
        $academicFilename = uniqid('academic_') . '.' . $academicExt;
        $paymentFilename = uniqid('payment_') . '.' . $paymentExt;
        
        $academicPath = $uploadDir . $academicFilename;
        $paymentPath = $uploadDir . $paymentFilename;

        // Move uploaded files
        if (!move_uploaded_file($_FILES['academicDoc']['tmp_name'], $academicPath)) {
            throw new Exception("Failed to upload academic document.");
        }
        
        if (!move_uploaded_file($_FILES['paymentProof']['tmp_name'], $paymentPath)) {
            throw new Exception("Failed to upload payment proof.");
        }

        $entry = [
            'name' => $name,
            'course' => $course,
            'phone' => $phone,
            'email' => $email,
            'district' => $district,
            'academicDoc' => $academicPath,
            'paymentProof' => $paymentPath,
            'submittedAt' => date('Y-m-d H:i:s')
        ];

        $dataFile = 'admissions.json';
        $allEntries = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
        $allEntries[] = $entry;

        file_put_contents($dataFile, json_encode($allEntries, JSON_PRETTY_PRINT));

        $response['success'] = true;
        $response['message'] = 'Your application was submitted successfully. We shall reach back to you through email or phone!';
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>