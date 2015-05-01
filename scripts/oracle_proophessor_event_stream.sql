--
-- Tabellenstruktur für Tabelle `proophessor_event_stream`
--

CREATE TABLE IF NOT EXISTS "proophessor_event_stream" (
  "event_id" varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  "version" int(11) NOT NULL,
  "event_name" varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  "event_class" varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  "payload" longtext COLLATE utf8_unicode_ci NOT NULL,
  "created_at" varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  "aggregate_id" varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  "aggregate_type" varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  "causation_id" varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  "causation_name" varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  "dispatch_status" int(11) NOT NULL
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `proophessor_event_stream`
--
ALTER TABLE `proophessor_event_stream`
 ADD PRIMARY KEY ("event_id"), ADD UNIQUE KEY "ph_es_metadata_version_uix" ("aggregate_id","aggregate_type","version");