# Simple Door App for Owl Platform #

Author: Robert Moore
Version: 1.0.0

## Door to Name XML Format ##
A simple XML document that maps many people to each room.  The room names
should match the World Model "displayName" attribute values for the room/door
entries.

    <?xml version="1.0"?>
    <PeopleDirectory>
      <person>
        <name>John Smith</name>
        <room>Room A</room>
      </person>
      <person>
        <name>Jane Smith</name>
        <room>Room B</room>
      </person>
      <person>
        <name>Jack Smith</name>
        <room>Room B</room>
      </person>
    </PeopleDirectory>
