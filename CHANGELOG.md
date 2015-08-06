# CHANGELOG


## v2.0.2
* issue #5: Load ActiveEdits script only when editing an object.


## v2.0.1
* issue #3: [HeartbeatCmsController] Ensure that a lock for a given slug expires in 10 seconds, not 1 hour.
* issue #3: [HeartbeatCmsController] Reduce cache ttl to 2 minutes for all active edit keys.


## v2.0.0
* issue #1: Removed all NCR references and store data in cache.
* issue #1: Create an SQL purge script to remove all `active_edit_*` tables, and all related tags with `ElementID` and `Role=active-edit-record` from all tables.

> Note: ElementID is different per site.


## v1.4.0
* Removed _updateSaveMessage method and calls to it, with the updated history plugin, history elements/aspects/etc are no longer available and this method is no longer valid. Ticket #221
* Removed config `$properties['active.edit.saved.record.check.frequency']`.


## v1.3.0
* Added maintenance cli controller, /cli/activeeditmaintenance/deleteInactiveRecords?hourOffset=6. This allows a cron/etc to delete inactive nodes still in 'draft' mode.
* Updating api/find-all requests to specific Status.eq = 'draft'.  This should speed up active-edit queries greatly.  Code relies on active-edit records to be 'draft' mode (and currently active-edit nodes are never published, in 'draft' or 'deleted')


## v1.2.0
* Tagging active-edit does not generate a history record. Ticket #2251


## v1.1.0
* initial versioned commit
