USE workhronolic;

-- Run this once for an existing Workhronolic database.
-- Fresh installations already receive this column from schema.sql.
ALTER TABLE time_entries
  ADD COLUMN review_comment TEXT NULL AFTER note;
