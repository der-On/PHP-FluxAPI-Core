## URL Query strings

### Problem
How can we get complex queries into an URL query string

SQL:

    (time>=50 AND time<=100) OR ( (title="hans" OR type IN ('1',2','4') )

JSON:

    {
      "time": {
        "$>=": "50",
        "$<=": "100",
      },
      "$or": [
        {
          "$or": [
            { "title": "hans" }
            { "type": { "$in": ['1','2','4'] } }
          ]
        }]
      }
    }

URL:

    ?@json=[JSON code from above]


### Solution
introduce URL query filters.

A filter has "@" as a prefix and is mapped to a filter function. It appears in the form "@filter=param1,param2,...".
Parameters are listed comma seperated and passed to the filter function in the same order. Plugins can create own filters.

Complex example:

    ?active=1&@select=title,name&@range=time,50,100&@from=time,10&@order=time,DESC&@limit=0,10

- "active=1" equals to SQL: "WHERE active = 1"
- "@select=title,name" equals to SQL: "SELECT title,name"
- "@range=time,50,100" equals to SQL: "WHERE (time >= 50 AND time <= 100)"
- "@order=time,DESC" equals to SQL: "ORDER BY time DESC"
- "@limit=0,10" equals to SQL: "LIMIT 0,10"


### In PHP this could look like

    $api->loadProjects(
      new Query()
      ->filter([filter name],array(param1,param2,param3))
      ->filter(...)
      ->filter(...)
      ...
    );


Taken from the example above it could look as follows:

    $api->loadProjects(
      new Query()
      ->filter("equals",array("active","1"))
      ->filter("select",array("title","name"))
      ->filter("range",array("time",50,100))
      ->filter("order",array("time","DESC"))
      ->filter("limit",array(0,10))
    );