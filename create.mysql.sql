CREATE TABLE events (
	page_id INT UNSIGNED NOT NULL DEFAULT 0,
	start_at INT UNSIGNED NOT NULL,
	end_at INT UNSIGNED NOT NULL,
	summary TEXT NULL,
	url VARCHAR(255) NULL,
	deleted BOOLEAN DEFAULT 0,
	KEY date_idx (page_id,start_at)
);
