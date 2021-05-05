<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>three.js Cabin</title>
    <style>
    body {
        background-color: #4a4a4a;
        margin: 0;
        overflow: hidden;
    }
    </style>
</head>

<body>

    <script type="module">
    import * as THREE from 'https://www.emanuelfavreau.com/nina/js/three.module.js';
    import {
        OBJLoader
    } from 'https://www.emanuelfavreau.com/nina/js/OBJLoader.js';
    import {
        MTLLoader
    } from 'https://www.emanuelfavreau.com/nina/js/MTLLoader.js';
    import {
        OrbitControls
    } from 'https://www.emanuelfavreau.com/nina/js/OrbitControls.js';


    let container;

    let camera, scene, renderer;

    let mouseX = 0,
        mouseY = 0;

    let windowHalfX = window.innerWidth / 2;
    let windowHalfY = window.innerHeight / 2;

    let object;

    let controls;

    let posX;
    let posY;
    let posZ;

let objName = "6466_OBJ2";

    let pref = "./";
    let suffobj = ".obj";
    let suffmtl = ".mtl";
    let objLongName = objName + suffobj;
    let mtlLongName = objName + suffmtl;

    init();
    animate();

    function init() {

        container = document.createElement('div');
        document.body.appendChild(container);

        // camera
        camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 1, 2000);
        camera.position.z = 250;

        // scene
        scene = new THREE.Scene();

        // lights
        const ambientLight = new THREE.AmbientLight(0xf0e0ba, 0.4);
        scene.add(ambientLight);

        const skyColor = 0xB1E1FF; // light blue
        const groundColor = 0xB97A20; // brownish orange
        const intensity = 0.5;
        const light = new THREE.HemisphereLight(skyColor, groundColor, intensity);
        scene.add(light);

        const light2 = new THREE.DirectionalLight(0xFFFFFF, 1.3);
        light2.position.set(0, 1, 1);
        light2.target.position.set(-5, -5, 0);
        scene.add(light2);
        scene.add(light2.target);

        scene.add(camera);

        // manager
        function loadModel() {
            object.traverse(function(child) {

                if (child.isMesh) {
                    child.material.color = 0xffb830;
                }

            });

            object.position.y = -150;
            scene.add(object);
        }

        const manager = new THREE.LoadingManager(loadModel);

        manager.onProgress = function(item, loaded, total) {
            console.log(item, loaded, total);
        };

        // texture
        new MTLLoader(manager)
            .setPath(pref)
            .load(mtlLongName, function(materials) {

                materials.preload();

                new OBJLoader(manager)
                    .setMaterials(materials)
                    .setPath(pref)
                    .load(objLongName, function(object) {

                        object.position.y = -150;

                        posY = object.position.y;
                        object.position.x = 0;
                        posX = object.position.x;
                        object.position.z = 0;
                        posZ = object.position.z;

                        scene.add(object);

                        getCenter(object);

                    }, onProgress, onError);

            });


        // model
        function onProgress(xhr) {

            if (xhr.lengthComputable) {

                const percentComplete = xhr.loaded / xhr.total * 100;
                console.log('model ' + Math.round(percentComplete, 2) + '% downloaded');

            }

        }

        function onError() {}

        const loader = new OBJLoader(manager);
        loader.load(pref + objLongName, function(obj) {

            object = obj;

        }, onProgress, onError);


        function getCenter(object) {

            const box = new THREE.BoxHelper(object, 0xffff00);

            var geometry = box.geometry;
            geometry.computeBoundingBox();
            var center = new THREE.Vector3();
            geometry.boundingBox.getCenter(center);
            object.localToWorld(center);
            posX = center.x;
            posY = center.y;
            posZ = center.z

            controls = new OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.25;
            controls.enableZoom = true;
            controls.target.set(posX, posY + 150, posZ);
            controls.update();
        }

        // render
        renderer = new THREE.WebGLRenderer( { antialias: true } );
        renderer.setPixelRatio(window.devicePixelRatio);
        renderer.setSize(window.innerWidth, window.innerHeight);
        container.appendChild(renderer.domElement);

        window.addEventListener('resize', onWindowResize);


    }

    function onWindowResize() {

        windowHalfX = window.innerWidth / 2;
        windowHalfY = window.innerHeight / 2;

        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();

        renderer.setSize(window.innerWidth, window.innerHeight);

    }

    function animate() {

        requestAnimationFrame(animate);
        renderer.render(scene, camera);

    }
    </script>
</body>

</html>
