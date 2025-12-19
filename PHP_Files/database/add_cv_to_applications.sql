-- Add CV upload field to JobApplications table
ALTER TABLE JobApplications 
ADD COLUMN cv_file_path VARCHAR(255) NULL AFTER cover_letter;
