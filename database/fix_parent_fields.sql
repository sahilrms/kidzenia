-- Add mother and father name fields to students table
USE kidzenia_db;

ALTER TABLE students 
ADD COLUMN mother_name VARCHAR(100) AFTER gender,
ADD COLUMN father_name VARCHAR(100) AFTER mother_name;

-- Update existing records that have parent info in address field
UPDATE students 
SET 
    mother_name = CASE 
        WHEN address LIKE '%Mother:%' THEN 
            SUBSTRING(address, LOCATE('Mother:', address) + 8, 
                   CASE WHEN LOCATE('|', address, LOCATE('Mother:', address)) > 0 
                        THEN LOCATE('|', address, LOCATE('Mother:', address)) - LOCATE('Mother:', address) - 8
                        ELSE LENGTH(address) END)
        ELSE NULL 
    END,
    father_name = CASE 
        WHEN address LIKE '%Father:%' THEN 
            SUBSTRING(address, LOCATE('Father:', address) + 8, 
                   CASE WHEN LOCATE('|', address, LOCATE('Father:', address)) > 0 
                        THEN LOCATE('|', address, LOCATE('Father:', address)) - LOCATE('Father:', address) - 8
                        ELSE LENGTH(address) END)
        ELSE NULL 
    END
WHERE address LIKE '%Mother:%' OR address LIKE '%Father:%';

-- Clean address field to remove parent information
UPDATE students 
SET address = CASE 
    WHEN address LIKE '%Email:%' THEN 
        SUBSTRING(address, LOCATE('Email:', address) + 7, 
               CASE WHEN LOCATE('|', address, LOCATE('Email:', address)) > 0 
                    THEN LOCATE('|', address, LOCATE('Email:', address)) - LOCATE('Email:', address) - 7
                    ELSE LENGTH(address) END)
    WHEN address LIKE '%Phone:%' THEN 
        SUBSTRING(address, LOCATE('Phone:', address) +7, 
               CASE WHEN LOCATE('|', address, LOCATE('Phone:', address)) > 0 
                    THEN LOCATE('|', address, LOCATE('Phone:', address)) - LOCATE('Phone:', address) -7
                    ELSE LENGTH(address) END)
    ELSE address 
END
WHERE address LIKE '%Mother:%' OR address LIKE '%Father:%';
