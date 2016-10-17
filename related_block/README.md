Example of creating a custom block.

** Assumptions/Setup

* Vocabulary called "Tags"
* Field on node called "field_related_events" that is a multi-value entity
reference to "related nodes"
* Field on node called "field_related_tag" which is a taxonomy term reference
to pull in nodes with a matching tag
* Field on node called "field_tags" which is a multivalue taxonomy term 
reference that gives the tags for a given node.

This block combines the curated list of directly related nodes with an
automatic list of nodes that are tagged with a matching value.  Dups are
removed and the resulting list is limited to a specific number of results.

