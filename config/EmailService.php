<?php
require_once 'database.php';

class EmailService {
    private $db;
    private $settings = [];
    
    public function __construct() {
        $this->db = new Database();
        $this->loadEmailSettings();
    }
    
    private function loadEmailSettings() {
        try {
            $conn = $this->db->getConnection();
            $query = "SELECT setting_key, setting_value FROM email_settings";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log("Failed to load email settings: " . $e->getMessage());
        }
    }
    
    public function sendEmail($to, $subject, $message, $fromEmail = null, $fromName = null, $isHtml = false) {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Email service is disabled'];
        }
        
        $fromEmail = $fromEmail ?? $this->settings['from_email'] ?? 'noreply@kidzenia.com';
        $fromName = $fromName ?? $this->settings['from_name'] ?? 'Kidzenia Kindergarten';
        
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        if ($isHtml) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        
        try {
            if ($this->useSMTP()) {
                return $this->sendSMTP($to, $subject, $message, $fromEmail, $fromName, $isHtml);
            } else {
                return $this->sendMail($to, $subject, $message, implode("\r\n", $headers));
            }
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
        }
    }
    
    private function sendMail($to, $subject, $message, $headers) {
        if (mail($to, $subject, $message, $headers)) {
            return ['success' => true, 'message' => 'Email sent successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email using mail() function'];
        }
    }
    
    private function sendSMTP($to, $subject, $message, $fromEmail, $fromName, $isHtml) {
        try {
            require_once 'PHPMailer/PHPMailer.php';
            require_once 'PHPMailer/SMTP.php';
            require_once 'PHPMailer/Exception.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = $this->settings['smtp_host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->settings['smtp_username'] ?? '';
            $mail->Password = $this->settings['smtp_password'] ?? '';
            $mail->SMTPSecure = $this->settings['smtp_encryption'] ?? 'tls';
            $mail->Port = (int)($this->settings['smtp_port'] ?? '587');
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            if (!$isHtml) {
                $mail->AltBody = strip_tags($message);
            }
            
            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully via SMTP'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'SMTP Error: ' . $e->getMessage()];
        }
    }
    
    public function sendBulkEmail($recipients, $subject, $message, $fromEmail = null, $fromName = null, $isHtml = false) {
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($recipients as $to) {
            $result = $this->sendEmail($to, $subject, $message, $fromEmail, $fromName, $isHtml);
            $results[$to] = $result;
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }
        
        return [
            'success' => $failureCount === 0,
            'total' => count($recipients),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }
    
    public function testEmailConfiguration() {
        $testEmail = $this->settings['test_email'] ?? '';
        if (empty($testEmail)) {
            return ['success' => false, 'message' => 'Test email address not configured'];
        }
        
        $subject = 'Kidzenia Email Configuration Test';
        $message = 'This is a test email to verify that your email configuration is working correctly.';
        
        return $this->sendEmail($testEmail, $subject, $message);
    }
    
    public function isEnabled() {
        return ($this->settings['email_enabled'] ?? '0') === '1';
    }
    
    public function useSMTP() {
        return !empty($this->settings['smtp_host']) && !empty($this->settings['smtp_username']);
    }
    
    public function getSettings() {
        return $this->settings;
    }
    
    public function updateSetting($key, $value) {
        try {
            $conn = $this->db->getConnection();
            $query = "INSERT INTO email_settings (setting_key, setting_value) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP";
            $stmt = $conn->prepare($query);
            $stmt->execute([$key, $value, $value]);
            
            $this->settings[$key] = $value;
            return ['success' => true, 'message' => 'Setting updated successfully'];
        } catch (PDOException $e) {
            error_log("Failed to update email setting: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update setting: ' . $e->getMessage()];
        }
    }
    
    public function createEmailTemplate($templateKey, $variables = []) {
        try {
            $conn = $this->db->getConnection();
            $query = "SELECT * FROM email_templates WHERE template_key = ? AND is_active = 1";
            $stmt = $conn->prepare($query);
            $stmt->execute([$templateKey]);
            $templateData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$templateData) {
                return ['subject' => '', 'html' => '', 'text' => '', 'error' => 'Template not found'];
            }
            
            $template = [
                'subject' => $templateData['subject'],
                'html' => $templateData['html_content'],
                'text' => $templateData['text_content']
            ];
            
            // Replace variables
            foreach ($variables as $key => $value) {
                $placeholder = '{{' . $key . '}}';
                $template['subject'] = str_replace($placeholder, $value, $template['subject']);
                $template['html'] = str_replace($placeholder, $value, $template['html']);
                $template['text'] = str_replace($placeholder, $value, $template['text']);
            }
            
            return $template;
            
        } catch (PDOException $e) {
            error_log("Failed to load template: " . $e->getMessage());
            return ['subject' => '', 'html' => '', 'text' => '', 'error' => 'Database error'];
        }
    }
    
    public function getTemplate($templateKey) {
        try {
            $conn = $this->db->getConnection();
            $query = "SELECT * FROM email_templates WHERE template_key = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$templateKey]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get template: " . $e->getMessage());
            return null;
        }
    }
    
    public function getAllTemplates() {
        try {
            $conn = $this->db->getConnection();
            $query = "SELECT * FROM email_templates ORDER BY template_type, template_name";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get templates: " . $e->getMessage());
            return [];
        }
    }
    
    public function sendTemplateEmail($templateKey, $to, $variables = [], $fromEmail = null, $fromName = null) {
        $template = $this->createEmailTemplate($templateKey, $variables);
        
        if (isset($template['error'])) {
            return ['success' => false, 'message' => $template['error']];
        }
        
        return $this->sendEmail($to, $template['subject'], $template['html'], $fromEmail, $fromName, true);
    }
    
    public function sendBulkTemplateEmail($templateKey, $recipients, $variables = [], $fromEmail = null, $fromName = null) {
        $template = $this->createEmailTemplate($templateKey, $variables);
        
        if (isset($template['error'])) {
            return ['success' => false, 'message' => $template['error']];
        }
        
        return $this->sendBulkEmail($recipients, $template['subject'], $template['html'], $fromEmail, $fromName, true);
    }
}
?>
