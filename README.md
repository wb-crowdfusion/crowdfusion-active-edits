CHANGES
-------

2.0.0
    * Removed all NCR references and store data in cache.

1.4.0
    * Removed _updateSaveMessage method and calls to it, with the updated history plugin,
      history elements/aspects/etc are no longer available and this method is no longer valid. Ticket #221
    * Removed config `$properties['active.edit.saved.record.check.frequency']`.


1.3.0

    * Added maintenance cli controller, /cli/activeeditmaintenance/deleteInactiveRecords?hourOffset=6
      This allows a cron/etc to delete inactive nodes still in 'draft' mode.
    * Updating api/find-all requests to specific Status.eq = 'draft'.  This should
      speed up active-edit queries greatly.  Code relies on active-edit records to be
      'draft' mode (and currently active-edit nodes are never published, in 'draft' or 'deleted')

 -- eric.byers Wed 29 Feb 2012 21:40 CST

1.2.0

    * #2251 - Tagging active-edit does not generate a history record

 -- noel  Thu, 10 Feb 2011 17:00 EST

1.1.0

    * initial versioned commit

 -- kevin  Wed, 4 Feb 2011 16:15 EST
