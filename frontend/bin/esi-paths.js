#!/usr/bin/env node

'use strict';

const https = require('https');
const fs = require('fs');

const compatDate = '2020-01-01'; // Same date as in settings.php.
https.get(`https://esi.evetech.net/meta/openapi.json?compatibility_date=${compatDate}`, response => {
    let data = '';
    response.on('data', chunk => {
        data += chunk;
    })
    response.on('end', () => {
        fetchDone(JSON.parse(data));
    });
});

function fetchDone(def) {
    const get = [];
    const post = [];
    for (const path in def.paths) {
        if (!def.paths.hasOwnProperty(path)) {
            continue;
        }
        if (def.paths[path].get) {
            get.push('/latest' + path);
        } else if (def.paths[path].post) {
            post.push('/latest' + path);
        }
    }
    writeFiles(get, post);
}

function writeFiles(get, post) {
    fs.writeFile(
        __dirname + "/../../frontend/public/esi-paths-http-get.json",
        JSON.stringify(get, null, 2),
        function(err) {
            result(err, 'frontend/public/esi-paths-http-get.json');
        }
    );
    fs.writeFile(
        __dirname + "/../../frontend/public/esi-paths-http-post.json",
        JSON.stringify(post, null, 2),
        function(err) {
            result(err, 'frontend/public/esi-paths-http-post.json');
        }
    );
    function result(err, file) {
        if (!err) {
            console.log(`wrote ${file}`);
        } else {
            console.log(err);
        }
    }
}
