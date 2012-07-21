CREATE TABLE events (
	page_id INT UNSIGNED NOT NULL DEFAULT 0,
	date DATE NOT NULL DEFAULT '0000-00-00',
	description TEXT NOT NULL,
	visibility ENUM('important','public','connected','invisible') DEFAULT 'invisible' NOT NULL,
	KEY date_idx (page_id,date)
);

CREATE TABLE eventglobal (
	popuphtml TEXT NOT NULL
);


