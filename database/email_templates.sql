-- Email Templates Table
CREATE TABLE IF NOT EXISTS email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) UNIQUE NOT NULL,
    template_key VARCHAR(50) UNIQUE NOT NULL,
    subject TEXT NOT NULL,
    html_content TEXT,
    text_content TEXT,
    template_type ENUM('system', 'custom') DEFAULT 'custom',
    variables TEXT,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Default System Templates
INSERT INTO email_templates (template_name, template_key, subject, html_content, text_content, template_type, variables, description) VALUES 
('Welcome Email', 'welcome', 'Welcome to Kidzenia Kindergarten', 
'<h1>Welcome {{name}}!</h1>
<p>Thank you for joining Kidzenia Kindergarten. We are excited to have you with us.</p>
<p><strong>Your Details:</strong></p>
<ul>
    <li>Username: {{username}}</li>
    <li>Email: {{email}}</li>
    <li>Role: {{role}}</li>
</ul>
<p>If you have any questions, please don''t hesitate to contact us.</p>
<p>Best regards,<br>Kidzenia Kindergarten Team</p>', 
'Welcome {{name}}!

Thank you for joining Kidzenia Kindergarten. We are excited to have you with us.

Your Details:
- Username: {{username}}
- Email: {{email}}
- Role: {{role}}

If you have any questions, please don''t hesitate to contact us.

Best regards,
Kidzenia Kindergarten Team', 
'system', 'name,username,email,role', 'Welcome email for new users'),

('Announcement', 'announcement', '{{title}}', 
'<h2>{{title}}</h2>
<p>{{content}}</p>
<p><strong>Date:</strong> {{date}}</p>
<p><strong>From:</strong> {{sender}}</p>
<hr>
<p><em>This is an automated announcement from Kidzenia Kindergarten.</em></p>', 
'{{title}}

{{content}}

Date: {{date}}
From: {{sender}}

---
This is an automated announcement from Kidzenia Kindergarten.', 
'system', 'title,content,date,sender', 'General announcement template'),

('Password Reset', 'password_reset', 'Password Reset Request', 
'<h1>Password Reset</h1>
<p>Hello {{name}},</p>
<p>You requested to reset your password. Click the link below to reset your password:</p>
<p><a href="{{reset_link}}" style="background-color: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Reset Password</a></p>
<p>If you didn''t request this password reset, please ignore this email.</p>
<p><strong>Note:</strong> This link will expire in {{expiry_hours}} hours.</p>
<p>Best regards,<br>Kidzenia Kindergarten Team</p>', 
'Hello {{name}},

You requested to reset your password. Visit this link to reset your password:
{{reset_link}}

If you didn''t request this password reset, please ignore this email.

Note: This link will expire in {{expiry_hours}} hours.

Best regards,
Kidzenia Kindergarten Team', 
'system', 'name,reset_link,expiry_hours', 'Password reset email template'),

('Student Attendance Alert', 'attendance_alert', 'Attendance Alert - {{student_name}}', 
'<h2>Attendance Alert</h2>
<p>Dear {{parent_name}},</p>
<p>This is to inform you that your child <strong>{{student_name}}</strong> was marked as <strong>{{attendance_status}}</strong> on {{date}}.</p>
<p><strong>Details:</strong></p>
<ul>
    <li>Student: {{student_name}}</li>
    <li>Date: {{date}}</li>
    <li>Status: {{attendance_status}}</li>
    <li>Check-in: {{check_in_time}}</li>
    <li>Check-out: {{check_out_time}}</li>
    <li>Notes: {{notes}}</li>
</ul>
<p>If you have any questions about this attendance record, please contact us.</p>
<p>Best regards,<br>Kidzenia Kindergarten</p>', 
'Dear {{parent_name}},

This is to inform you that your child {{student_name}} was marked as {{attendance_status}} on {{date}}.

Details:
- Student: {{student_name}}
- Date: {{date}}
- Status: {{attendance_status}}
- Check-in: {{check_in_time}}
- Check-out: {{check_out_time}}
- Notes: {{notes}}

If you have any questions about this attendance record, please contact us.

Best regards,
Kidzenia Kindergarten', 
'system', 'parent_name,student_name,date,attendance_status,check_in_time,check_out_time,notes', 'Attendance notification to parents'),

('Event Reminder', 'event_reminder', 'Reminder: {{event_title}}', 
'<h2>Event Reminder</h2>
<p>Dear {{name}},</p>
<p>This is a friendly reminder about the upcoming event:</p>
<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
    <h3>{{event_title}}</h3>
    <p><strong>Date:</strong> {{event_date}}</p>
    <p><strong>Time:</strong> {{event_time}}</p>
    <p><strong>Location:</strong> {{event_location}}</p>
    <p><strong>Description:</strong> {{event_description}}</p>
</div>
<p>We look forward to seeing you there!</p>
<p>Best regards,<br>Kidzenia Kindergarten</p>', 
'Dear {{name}},

This is a friendly reminder about the upcoming event:

{{event_title}}
Date: {{event_date}}
Time: {{event_time}}
Location: {{event_location}}
Description: {{event_description}}

We look forward to seeing you there!

Best regards,
Kidzenia Kindergarten', 
'system', 'name,event_title,event_date,event_time,event_location,event_description', 'Event reminder notification');
