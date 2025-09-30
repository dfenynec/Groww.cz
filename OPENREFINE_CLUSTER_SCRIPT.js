[
  {
    "op": "core/row-reorder",
    "description": "Reorder rows",
    "mode": "row-based",
    "sorting": {
      "criteria": [
        {
          "errorPosition": 1,
          "caseSensitive": false,
          "valueType": "string",
          "column": "Keyword",
          "blankPosition": 2,
          "reverse": false
        }
      ]
    }
  },
  {
    "op": "core/blank-down",
    "description": "Blank down cells in column ﻿Keyword",
    "engineConfig": {
      "facets": [],
      "mode": "row-based"
    },
    "columnName": "Keyword"
  },
  {
    "op": "core/multivalued-cell-join",
    "description": "Join multi-valued cells in column Keyword-bu",
    "columnName": "Keyword-bu",
    "keyColumnName": "Keyword",
    "separator": "☼"
  },
  {
    "op": "core/multivalued-cell-join",
    "description": "Join multi-valued cells in column Search",
    "columnName": "Search",
    "keyColumnName": "Keyword",
    "separator": ", "
  },
  {
    "op": "core/text-transform",
    "description": "Text transform on cells in column Search using expression value.toString()",
    "engineConfig": {
      "facets": [],
      "mode": "row-based"
    },
    "columnName": "Search",
    "expression": "value.toString().replace('.0', '')",
    "onError": "keep-original",
    "repeat": false,
    "repeatCount": 10
  },
  {
    "op": "core/column-addition",
    "description": "Create column Index at index 3 based on column Search using expression jython:array = value\narray = array.split(',')\n\nx = 0\nmax = 0\nindex = 0\n\nwhile x < len(array):\n array[x] = int(array[x])\n if max < array[x]:\n  max = array[x]\n  index = array.index(max)\n x = x + 1\n\nreturn index",
    "engineConfig": {
      "facets": [],
      "mode": "row-based"
    },
    "newColumnName": "Index",
    "columnInsertIndex": 3,
    "baseColumnName": "Search",
    "expression": "jython:array = value\narray = array.split(',')\n\nx = 0\nmax = 0\nindex = 0\n\nwhile x < len(array):\n array[x] = int(array[x])\n if max < array[x]:\n  max = array[x]\n  index = array.index(max)\n x = x + 1\n\nreturn index",
    "onError": "set-to-blank"
  },
  {
    "op": "core/column-removal",
    "description": "Remove column Keyword",
    "columnName": "Keyword"
  },
  {
    "op": "core/column-addition",
    "description": "Create column Keyword at index 1 based on column Keyword-bu using expression grel:forEach(value.split('☼'),v,v).get(cells['Index'].value)",
    "engineConfig": {
      "facets": [],
      "mode": "row-based"
    },
    "newColumnName": "Keyword",
    "columnInsertIndex": 1,
    "baseColumnName": "Keyword-bu",
    "expression": "grel:forEach(value.split('☼'),v,v).get(cells['Index'].value)",
    "onError": "set-to-blank"
  }
]