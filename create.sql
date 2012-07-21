CREATE TABLE events (
	page_id INT UNSIGNED NOT NULL DEFAULT 0,
	start_at DATETIME NOT NULL,
	end_at DATETIME NOT NULL,
	summary TEXT NOT NULL,
	KEY date_idx (page_id,start_at)
);

CREATE TABLE eventglobal (
	popuphtml TEXT NOT NULL
);


