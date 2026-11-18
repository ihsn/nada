<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Citations admin API (api/admin/citations).
 */

// DOI lookup (the "Look up" button of the citation form): Crossref first, then DataCite if
// Crossref has no record. Only those two fixed hosts are contacted.
$config['crossref_enabled'] = TRUE;
// Contact address for Crossref's "polite pool" (faster, more reliable). Left empty it is not sent.
// Set it to an address the organisation is happy to share with Crossref.
$config['crossref_mailto'] = '';

// Upload limits for imports and attachments
$config['citations_import_max_bytes'] = 5 * 1024 * 1024;
$config['citations_import_max_rows']  = 500;
// File types that can be attached to a citation
$config['citations_attachment_types'] = 'pdf|doc|docx|txt|zip';
