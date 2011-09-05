// The manifest file
//= require ./test/bar.js
//= require ./test/foo.js
//= depend_on ./test/dependency.js

/**
 * Some multiline comment which isn't modified
 */

/*
 *= require ./test/bar
 */

var foo = function() {
}();
