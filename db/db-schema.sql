BEGIN TRANSACTION;

CREATE TABLE "urls_store" (
	[store_id] VARCHAR UNIQUE NOT NULL,
	[user_id] VARCHAR NOT NULL,
	[domain_id] VARCHAR NOT NULL,
	[event] VARCHAR NOT NULL,
	[store_time_created] VARCHAR NOT NULL,
	[store_time_modified] VARCHAR NOT NULL,
	[store_index] VARCHAR NOT NULL,
	[store_slug] VARCHAR NOT NULL,
	[store_url] TEXT NOT NULL
);

CREATE INDEX "urls_store_ids_user" ON "urls_store" ([store_id], [user_id]);
CREATE INDEX "urls_store_ids_domain" ON "urls_store" ([store_id], [domain_id]);

CREATE TABLE "urls_domains" (
	[domain_id] VARCHAR UNIQUE NOT NULL,
	[user_id] VARCHAR NOT NULL,
	[event] VARCHAR NOT NULL,
	[domain_time_created] VARCHAR NOT NULL,
	[domain_time_modified] VARCHAR NOT NULL,
	[domain_master] VARCHAR NOT NULL,
	[domain_service] VARCHAR NOT NULL,
	[domain_enable] SMALLINT NULL
);

CREATE INDEX "urls_domains_ids_user" ON "urls_store" ([domain_id], [user_id]);

CREATE TABLE "urls_users" (
	[user_id] VARCHAR UNIQUE NOT NULL,
	[user_acl] VARCHAR NOT NULL,
	[event] VARCHAR NOT NULL,
	[user_time_created] VARCHAR NOT NULL,
	[user_time_modified] VARCHAR NOT NULL,
	[user_pending] VARCHAR NULL,
	[user_email] VARCHAR NOT NULL,
	[user_name] VARCHAR UNIQUE NOT NULL,
	[user_pass] VARCHAR NOT NULL,
	[user_notify] SMALLINT NULL
);

CREATE TABLE "urls_shadows" (
	[event] VARCHAR NOT NULL,
	[shadow_time] VARCHAR NOT NULL,
	[shadow_blob] TEXT NOT NULL
);

END TRANSACTION;
