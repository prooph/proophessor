--
-- Tabellenstruktur für Tabelle `proophessor_event_stream`
--

CREATE TABLE IF NOT EXISTS "proophessor_event_stream" (
  "eventId" varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  "version" int(11) NOT NULL,
  "eventName" varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  "payload" longtext COLLATE utf8_unicode_ci NOT NULL,
  "occurredOn" varchar(100) COLLATE utf8_unicode_ci NOT NULL,
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
 ADD PRIMARY KEY ("eventId"), ADD UNIQUE KEY "ph_es_metadata_version_uix" ("aggregate_id","aggregate_type","version");