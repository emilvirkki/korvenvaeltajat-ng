<?php

namespace App;

use Contentful\Delivery\Query;

class ContentfulContentService implements ContentService {
    private $client;
    private $filter_methods = array(
        'content_type' => 'setContentType',
        'limit' => 'setLimit',
        'order_by' => 'orderBy',
    );

    function __construct($client) {
        $this->client = $client;
    }

    public function getAll($filters = array()) {
        $query = new Query();

        foreach ($filters as $key => $value) {
            // More complex where clauses
            if ($key === 'where') {
                foreach ($value as $where_clause) {
                    call_user_func_array(array($query, 'where'), $where_clause);
                }
            // Built-in stuff (type, limit etc.)
            } else if (in_array($key, array_keys($this->filter_methods))) {
                $query->{$this->filter_methods[$key]}($value);
            // Simple where
            } else {
                $query->where($key, $value);
            }
        }

        return $this->entriesToArray(
            $this->client->getEntries($query)
        );
    }

    public function getOne($filters = array()) {
        $filters['limit'] = 1;
        $entries = $this->getAll($filters);

        if (count($entries) === 0) {
            return null;
        }

        return $entries[0];
    }

    private function entriesToArray($raw_entries)
    {
        $entries = array();
        foreach ($raw_entries as $raw_entry) {
            $entries[] = $this->entryToArray($raw_entry);
        }
        return $entries;
    }

    private function entryToArray($entry)
    {
        $fields = $entry->getContentType()->getFields();
        $arr = array();
        foreach ($fields as $id => $field) {
            if ($field->getType() === 'Array') {
                $arr[$id] = $this->arrayFieldToArray($entry[$id], $field);
            } else if ($field->getType() === 'Date' && $entry[$id]->getTimezone()->getName() === 'UTC') {
                // This is a terrible hack - Contentful treats dates with no timezone
                // as UTC -> need to manually correct them to local timezone. Of course,
                // this hack mean that we can't deal with UTC dates anywhere...
                $tz_offset = date('Z'); // timezone offset from UTC
                $original_timestamp = $entry[$id]->getTimestamp();
                $fixed_timestamp = $original_timestamp - $tz_offset;
                $arr[$id] = new \DateTimeImmutable("@$fixed_timestamp");
            } else {
                // Some field types e.g. links will throw an error here - that's fine for now
                $arr[$id] = strval($entry[$id]);
            }
        }
        $arr['created'] = $entry->getSystemProperties()->getCreatedAt();
        return $arr;
    }

    private function arrayFieldToArray($field_values, $field) {
        $arr = array();
        foreach ($field_values as $key => $value) {
            if ($field->getItemsLinkType() === 'Asset') {
                $arr[$key] = array(
                    'title' => $value->getTitle(),
                    'url' => $value->getFile()->getUrl(),
                    'content_type' => $value->getFile()->getContentType(),
                );
            } else {
                // Other link types will throw error here - that's fine for now
                $arr[$key] = strval($value);
            }
        }
        return $arr;
    }

}
