var dataCacheName = 'dataConn';
var cacheName = 'shellConn';
var tplCacheName = 'tplConn';
var filesToCache = ["http://localhost/site-transportadora/","http://localhost/site-transportadora/index.php","http://localhost/site-transportadora/uploads/image/2018/05/marca-fenix-cargo.png","http://localhost/site-transportadora/uploads/image/2018/05/favicon.png","http://localhost/site-transportadora/assetsPublic/app/app.js","http://localhost/site-transportadora/assetsPublic/boot/boot.js","http://localhost/site-transportadora/assetsPublic/jquery/jquery.min.js","http://localhost/site-transportadora/assetsPublic/mustache/mustache.js","http://localhost/site-transportadora/assetsPublic/panel/panel.js","http://localhost/site-transportadora/assetsPublic/panel/panel.css","http://localhost/site-transportadora/assetsPublic/theme/theme.css","http://localhost/site-transportadora/assetsPublic/w3/w3.css","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/button_icon.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/col_1.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/col_2.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/col_3.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/col_4.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/col_5.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/col_6.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/demo.png","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/h1.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/h2.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/h3.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/h4.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/h5.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/h6.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/img.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/input_icon.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/parallax.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/post_card.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/post_flat.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/section_large.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/setcion_full.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/setcion_medium.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/setcion_small.mst","http://localhost/site-transportadora/vendor/conn/link-control/tplFront/ul.mst"];

self.addEventListener('install', function (e) {
    console.log('[ServiceWorker] Install');
    e.waitUntil(
        caches.open(cacheName).then(function (cache) {
            console.log('[ServiceWorker] Caching app shell');
            return cache.addAll(filesToCache);
        })
    );
});
self.addEventListener('activate', function (e) {
    console.log('[ServiceWorker] Activate');
    e.waitUntil(
        caches.keys().then(function (keyList) {
            return Promise.all(keyList.map(function (key) {
                if (key !== cacheName && key !== dataCacheName && key !== tplCacheName) {
                    console.log('[ServiceWorker] Removing old cache', key);
                    return caches.delete(key);
                }
            }));
        })
    );
    return self.clients.claim();
});
self.addEventListener('fetch', function(e) {
    console.log('[Service Worker] Fetch', e.request.url);

    if (e.request.url.indexOf(HOME + 'request/post') > -1) {
        /*
         * DATA
         */
        e.respondWith(
            caches.open(dataCacheName).then(function(cache) {
                return fetch(e.request).then(function(response){
                    cache.put(e.request.url, response.clone());
                    return response;
                });
            })
        );
    } else if(e.request.url.indexOf(HOME + 'vendor/conn/link-control/tplFront') > -1){
        /*
         * TEMPLATE
         */
        e.respondWith(
            caches.open(tplCacheName).then(function(cache) {
                return fetch(e.request).then(function(response){
                    cache.put(e.request.url, response.clone());
                    return response;
                });
            })
        );
    } else {
        /*
         * SHELL
         */
        e.respondWith(
            caches.match(e.request).then(function(response) {
                return response || fetch(e.request);
            })
        );
    }
});