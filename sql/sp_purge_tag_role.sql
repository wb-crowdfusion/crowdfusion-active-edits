-- $Id: sp_purge_tag_role.sql 174 2015-05-03 10:59:45Z michaelarieli $
--
-- This function purge all entries (from current database) which point to
-- an element_id and a Role. Function will loop through all tables and use paging
-- to delete rows to prevent database/table lock.
--
-- use: call sp_purge_tag_role(element_id, role_name);

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_purge_tag_role $$
CREATE PROCEDURE sp_purge_tag_role(element_id INT, role_name VARCHAR(255))
BEGIN

    DECLARE current_table VARCHAR(255);
    DECLARE end_of_tables TINYINT DEFAULT 0;

    DECLARE _cursor CURSOR FOR
        SELECT t.TABLE_NAME
        FROM information_schema.TABLES t    LEFT JOIN information_schema.COLUMNS c
            ON (t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_SCHEMA = c.TABLE_SCHEMA)
        WHERE t.TABLE_SCHEMA = DATABASE()
            AND t.TABLE_TYPE = 'BASE TABLE'
            AND (c.COLUMN_NAME = 'ElementID' OR c.COLUMN_NAME = 'Role' OR c.COLUMN_NAME = 'TagID')
        GROUP BY t.TABLE_NAME
        HAVING COUNT(1) = 3
        ORDER BY t.TABLE_NAME ASC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET end_of_tables = 1;

    OPEN _cursor;

    IF element_id <= 0 AND (role_name IS NULL OR role_name = '') THEN
        SET end_of_tables = 1;
    END IF;

    purge_table: LOOP
        FETCH _cursor INTO current_table;

        IF end_of_tables = 1 THEN
            LEAVE purge_table;
        END IF;

        SET @batch_count = 50000;
        SET @min_id = 0;
        SET @max_id = 0;

        SET @s = CONCAT('SELECT MIN(TagID) INTO @min_id FROM ', current_table);
        PREPARE stmt FROM @s;
        EXECUTE stmt;

        SET @s = CONCAT('SELECT MAX(TagID) INTO @max_id FROM ', current_table);
        PREPARE stmt FROM @s;
        EXECUTE stmt;

        WHILE @min_id <= @max_id DO

            SET @s = CONCAT('DELETE FROM ', current_table, ' WHERE ElementID = ', element_id, ' AND Role = "', role_name, '" AND TagID >= ', @min_id, ' AND TagID <= ', (@min_id + @batch_count));
            PREPARE stmt FROM @s;
            EXECUTE stmt;

            SET @min_id = @min_id + @batch_count;

            SELECT SLEEP(10);

        END WHILE;

    END LOOP;

    CLOSE _cursor;

END $$

DELIMITER ;
