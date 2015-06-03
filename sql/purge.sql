-- #############################
-- ########## WARNING ##########
-- #############################
--
-- RUN THIS WITHOUT PROPER AUTHORITY AND YOU'LL PROBABLY BE FIRED... OR WORSE. (0_0)
--
-- STEP 1: Run the migration cli on all `active-edits` records.
-- STEP 2: Verify data in repository is correct and check some records in CMS for legacy data.
-- STEP 3: Backup the database and store it with the date.
-- STEP 4: Move backup to s3 with filename different from the backup created automatically nightly.
-- STEP 5: Copy backup to offsite server.
-- STEP 6: Run this script.
--
-- This is for sites upgrading from 1.x branch to 2.x which moves storage for
-- `active-edits` data from mysql to memcahce.
--

drop table n_active_edit;
drop table n_active_edit_intags;
drop table n_active_edit_meta_tiny;
drop table n_active_edit_outtags;

call sp_purge_tag_role(9, 'active-edit-record');
