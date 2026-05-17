-- Homepage CMS Content Table
CREATE TABLE IF NOT EXISTS `homepage_cms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section` varchar(50) NOT NULL,
  `content_key` varchar(100) NOT NULL,
  `content_type` enum('text','textarea','image','url','number','boolean') NOT NULL,
  `content_value` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_section_key` (`section`,`content_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default homepage content
INSERT INTO `homepage_cms` (`section`, `content_key`, `content_type`, `content_value`, `image_path`) VALUES
-- Header/Logo Section
('header', 'logo_text', 'text', 'Kidzenia', NULL),
('header', 'logo_text_span', 'text', 'Kindergarten', NULL),
('header', 'admin_login_text', 'text', 'Admin Login', NULL),

-- Hero Section
('hero', 'tag_text', 'text', 'Trusted Kindergarten For Little Learners', NULL),
('hero', 'tag_icon', 'text', 'fa-solid fa-star text-warning', NULL),
('hero', 'main_heading', 'text', 'Where', NULL),
('hero', 'main_heading_span', 'text', 'Curiosity', NULL),
('hero', 'main_heading_continued', 'text', 'Becomes Creativity', NULL),
('hero', 'hero_description', 'textarea', 'A joyful learning environment where children grow through imagination, play, discovery, and meaningful experiences designed for early childhood development.', NULL),
('hero', 'hero_image_path', 'image', 'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=1200&auto=format&fit=crop', NULL),
('hero', 'cta_button_text', 'text', 'Start Admission', NULL),
('hero', 'secondary_button_text', 'text', 'Explore Programs', NULL),
('hero', 'floating_card_1_title', 'text', 'Creative Learning', NULL),
('hero', 'floating_card_1_description', 'text', 'Interactive & playful education', NULL),
('hero', 'floating_card_1_icon', 'text', '🎨', NULL),
('hero', 'floating_card_2_title', 'text', 'Smart Transport', NULL),
('hero', 'floating_card_2_description', 'text', 'Live GPS tracking for parents', NULL),
('hero', 'floating_card_2_icon', 'text', '🚌', NULL),

-- Statistics
('stats', 'years_number', 'number', '12', NULL),
('stats', 'years_label', 'text', 'Years', NULL),
('stats', 'students_number', 'number', '850', NULL),
('stats', 'students_label', 'text', 'Students', NULL),
('stats', 'teachers_number', 'number', '40', NULL),
('stats', 'teachers_label', 'text', 'Teachers', NULL),

-- Features Section
('features', 'section_title', 'text', 'Why Choose Us', NULL),
('features', 'section_subtitle', 'text', 'Building Bright Futures', NULL),
('features', 'feature_1_title', 'text', 'Creative Programs', NULL),
('features', 'feature_1_description', 'textarea', 'Hands-on learning experiences that inspire creativity and imagination.', NULL),
('features', 'feature_1_icon', 'text', 'fa-solid fa-palette', NULL),
('features', 'feature_2_title', 'text', 'Safe Environment', NULL),
('features', 'feature_2_description', 'textarea', 'Secure campus with child-friendly infrastructure and caring educators.', NULL),
('features', 'feature_2_icon', 'text', 'fa-solid fa-heart', NULL),
('features', 'feature_3_title', 'text', 'Smart Curriculum', NULL),
('features', 'feature_3_description', 'textarea', 'Balanced academics, social development, and playful exploration.', NULL),
('features', 'feature_3_icon', 'text', 'fa-solid fa-book-open', NULL),
('features', 'feature_4_title', 'text', 'Live Tracking', NULL),
('features', 'feature_4_description', 'textarea', 'Parents stay connected with real-time transport monitoring.', NULL),
('features', 'feature_4_icon', 'text', 'fa-solid fa-bus', NULL),

-- Programs Section
('programs', 'section_title', 'text', 'Programs', NULL),
('programs', 'section_subtitle', 'text', 'Learning By Age', NULL),
('programs', 'program_1_title', 'text', 'Toddler Program', NULL),
('programs', 'program_1_age', 'text', 'Age 2 - 3', NULL),
('programs', 'program_1_description', 'textarea', 'Focus on sensory exploration, social interaction, and foundational communication skills.', NULL),
('programs', 'program_1_image', 'image', 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?q=80&w=1200&auto=format&fit=crop', NULL),
('programs', 'program_2_title', 'text', 'Nursery Program', NULL),
('programs', 'program_2_age', 'text', 'Age 3 - 4', NULL),
('programs', 'program_2_description', 'textarea', 'Interactive learning through storytelling, art, music, and engaging activities.', NULL),
('programs', 'program_2_image', 'image', 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=1200&auto=format&fit=crop', NULL),
('programs', 'program_3_title', 'text', 'Kindergarten', NULL),
('programs', 'program_3_age', 'text', 'Age 4 - 5', NULL),
('programs', 'program_3_description', 'textarea', 'School readiness program focused on confidence, creativity, and communication.', NULL),
('programs', 'program_3_image', 'image', 'https://images.unsplash.com/photo-1513258496099-48168024aec0?q=80&w=1200&auto=format&fit=crop', NULL),

-- CTA Section
('cta', 'section_title', 'text', 'Give Your Child The Best Start', NULL),
('cta', 'section_description', 'textarea', 'Join a nurturing environment where every child is celebrated, encouraged, and inspired to grow.', NULL),
('cta', 'button_text', 'text', 'Apply For Admission', NULL),

-- Gallery Section
('gallery', 'section_title', 'text', 'Gallery', NULL),
('gallery', 'section_subtitle', 'text', 'Moments Of Joy', NULL),

-- Contact Section
('contact', 'section_title', 'text', 'Contact Us', NULL),
('contact', 'section_subtitle', 'text', 'Get In Touch', NULL),
('contact', 'contact_description', 'textarea', 'Ready to give your child the best start? We''d love to hear from you!', NULL),
('contact', 'contact_button_text', 'text', 'Contact Form', NULL),
('contact', 'phone_button_text', 'text', 'Call Us', NULL),
('contact', 'map_url', 'url', '', NULL),

-- Footer
('footer', 'school_name', 'text', 'Kidzenia Kindergarten', NULL),
('footer', 'school_description', 'textarea', 'Creating joyful learning experiences for children through creativity, care, and innovation.', NULL),

-- Office Hours
('office_hours', 'monday_friday_label', 'text', 'Monday - Friday', NULL),
('office_hours', 'monday_friday_time', 'text', '8:00 AM - 4:00 PM', NULL),
('office_hours', 'saturday_label', 'text', 'Saturday', NULL),
('office_hours', 'saturday_time', 'text', '9:00 AM - 1:00 PM', NULL),
('office_hours', 'sunday_label', 'text', 'Sunday', NULL),
('office_hours', 'sunday_time', 'text', 'Closed', NULL),

-- Navigation Links
('nav', 'link_home', 'text', 'Home', NULL),
('nav', 'link_programs', 'text', 'Programs', NULL),
('nav', 'link_about', 'text', 'About', NULL),
('nav', 'link_gallery', 'text', 'Gallery', NULL),
('nav', 'link_events', 'text', 'Events', NULL),
('nav', 'link_contact', 'text', 'Contact', NULL);
