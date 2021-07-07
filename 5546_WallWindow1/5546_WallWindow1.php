<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <script src="//unpkg.com/dat.gui"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>

    <title>three.js Revit obj viewer</title>
    <style>
    body {
        font-family: Monospace;
        background: #9F9F9F;
        background-image: linear-gradient(rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.3)), radial-gradient(circle, #9F9F9F, #454647);
        color: #fff;
        margin: 0px;
        overflow: hidden;
    }
    </style>

</head>

<body>

    <script type="module">
    import * as THREE from "https://cdn.jsdelivr.net/npm/three@0.115/build/three.module.js";
    import {
        OrbitControls
    } from "https://cdn.jsdelivr.net/npm/three@0.115/examples/jsm/controls/OrbitControls.js";
    import {
        OBJLoader
    } from "https://cdn.jsdelivr.net/npm/three@0.115/examples/jsm/loaders/OBJLoader.js";
    import {
        MTLLoader
    } from "https://cdn.jsdelivr.net/npm/three@0.115/examples/jsm/loaders/MTLLoader.js";
    import {
        GLTFLoader
    } from "https://cdn.jsdelivr.net/npm/three@0.115/examples/jsm/loaders/GLTFLoader.js";
    import {
        DRACOLoader
    } from "https://cdn.jsdelivr.net/npm/three@0.115/examples/jsm/loaders/DRACOLoader.js";
    import {
        DragControls
    } from "https://cdn.jsdelivr.net/npm/three@0.115/examples/jsm/controls/DragControls.js";
    import {
        InstancedMesh
    } from "https://cdn.jsdelivr.net/npm/three@0.115/src/objects/InstancedMesh.js";
    import {
        GLTFExporter
    } from "https://cdn.jsdelivr.net/npm/three@0.115/examples/jsm/exporters/GLTFExporter.js";

    let scene;
    let camera;
    let renderer;
    let container;

    let controls;
    let posX, posY, posZ;

    let filename;
    var buttonisclicked = false;
    let reader;

    var objs = [];
    let myObj;
    var dummy = new THREE.Object3D();
    var count = 1;

    var inst;
    var zeobj;
    var instmesh;

    //gltf var
    let urlGltf;
    let startPosY = -150;
    let startBoxMeshScale = 1;
    let HandleScale = 1;
    let posXmem = 0;
    let posYmem = 0;
    let posZmem = 0;
    var model;
    var clock = new THREE.Clock();
    var boxgeometry, boxmaterial, boxmesh, boxHelper;
    let modelGroup;
    let modelReady = false;
    let spotLight;
    var arrmodelGroup = [];
    var arrboxHelper = [];
    var arrboxmesh = [];

    //gui
    let gui;
    let settings;
    var contrFld;
    var urlFld;

    //obj
let objName = "5546_WallWindow1";
    let pref = "./";
    let suffobj = ".obj";
    let suffmtl = ".mtl";
    let objLongName = objName + suffobj;
    let mtlLongName = objName + suffmtl;
    var urlprefix = "https://www.emanuelfavreau.com/nina/";
    var urlvalue = urlprefix + objName + "/" + objName.substring(5) + ".glb";
    let vericesReady = false;
    let firstvector;
    var selectedobjbyid;
    let object;
    var newmaterial;
    var gltfName;

    //test
    let dragControls;

    setup();
    draw();

    function setup() {
        setupScene();
        setupCamera();
        //setupMesh(); //light
        setupObjMtl();//with textures
        setupLights();
        setupRenderer();
        setupEventListeners();
        setupDragControl();
    }

    function setupScene() {
        scene = new THREE.Scene();
        //console.log("OK5");

        //materials test
        newmaterial = new THREE.MeshBasicMaterial({
            color: 0xa233a,
            //transparent: true,
            opacity: 0.4
        });
        newmaterial.name = "newMat";

        //container = document.createElement('div');
        //document.body.appendChild(container);

        // text
        var text2 = document.createElement('div');
        text2.style.position = 'absolute';
        text2.style.width = 100;
        text2.style.height = 100;
        text2.innerHTML = "";
        text2.style.top = 200 + 'px';
        text2.style.left = 200 + 'px';
        text2.style.color = "white";
        text2.id = "pp";
        text2.style.opacity = 0.0;
        document.body.style.fontSize = '20px';
        document.body.appendChild(text2);
        document.getElementById("pp").innerHTML = urlvalue;
    }

    function copyURL() {
        CopyToClipboard('pp');
    }

    function CopyToClipboard(containerid) {
        // Create a new textarea element and give it id='temp_element'
        const textarea = document.createElement('textarea')
        textarea.id = 'temp_element'
        // Optional step to make less noise on the page, if any!
        textarea.style.height = 0
        // Now append it to your page somewhere, I chose <body>
        document.body.appendChild(textarea)
        // Give our textarea a value of whatever inside the div of id=containerid
        textarea.value = document.getElementById(containerid).innerText
        // Now copy whatever inside the textarea to clipboard
        const selector = document.querySelector('#temp_element')
        selector.select()
        document.execCommand('copy')
        // Remove the textarea
        document.body.removeChild(textarea)
    }

    var raycaster = new THREE.Raycaster(); // Needed for object intersection
    var mouse = new THREE.Vector2(); //Needed for mouse coordinates
    var clr = 0xa0a0a; //couleur emissive


    function setupDragControl() {
        // dat.gui controls
        //const settings = { OrbitControl: true, Drag: true, add: setupAddInstance };
        settings = {
            OrbitControl: true,
            Drag: true,
            CopyURL: copyURL
        };
        gui = new dat.GUI();

        contrFld = gui.addFolder("Control");

        contrFld
            .add(settings, "OrbitControl")
            .onChange((enabled) => (controls.enabled = enabled));
        contrFld
            .add(settings, "Drag")
            .onChange((enabled) => (dragControls.enabled = enabled));

        //contrFld.open();

        // Setup drag controls
        dragControls = new DragControls(objs, camera, renderer.domElement);
        dragControls.transformRoot = true;

        dragControls.addEventListener("dragstart", () => (controls.enabled =
            false)); // Disable trackball controls while dragging
        dragControls.addEventListener(
            "dragend",
            () => settings.OrbitControl && (controls.enabled = true) && modifyPosEnd()
        ); // Re-enable Orbit controls

        urlFld = gui.addFolder("URL");
        urlFld.add(settings, "CopyURL");
        urlFld.open();

        document.body.appendChild(renderer.domElement);
    }


    function setupCamera() {
        let res = window.innerWidth / window.innerHeight;
        //camera = new THREE.PerspectiveCamera(75, res, 0.1, 1000);
        //camera.position.z = 3;

        camera = new THREE.PerspectiveCamera(45, res, 1, 2000);
        camera.position.z = 3;
    }

    function setupRenderer() {
        renderer = new THREE.WebGLRenderer({
            alpha: true,
            antialiase: true
        });
        //renderer.setSize(window.innerWidth, window.innerHeight, 2 );//resolution
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(2); //resolution
        renderer.shadowMap.enabled = true;
        //renderer.shadowMapSoft = true;
        //renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        document.body.appendChild(renderer.domElement);

        //window.addEventListener("click", onDocumentMouseDown, false);
    }

    function setupLights() {
        let ambientLight = new THREE.AmbientLight(0x0c0c0c, 20);
        scene.add(ambientLight);

        //var pointlight = new THREE.PointLight(0xffffff, 2);
        //pointlight.castShadow = true;
        //pointlight.shadow.radius = 8;
        //pointlight.shadow.mapSize.width = 2048;
        //pointlight.shadow.mapSize.height = 2048;
        //scene.add(pointlight);

        let spotLight = new THREE.SpotLight(0xffdf94, 5);
        spotLight.position.set(80, 20, 340);
        spotLight.target.position.set(-4, 0, -4);
        spotLight.angle = Math.PI / 4.0;
        spotLight.castShadow = true;
        //spotLight.shadow.camera.left = -500;
        //spotLight.shadow.camera.right = 500;
        spotLight.shadow.bias = -0.0001;
        spotLight.shadow.mapSize.width = 1024 * 4;
        spotLight.shadow.mapSize.height = 1024 * 4;
        spotLight.shadow.camera.near = 20; //shadow offset
        spotLight.shadow.camera.far = 800;
        spotLight.shadow.camera.fov = 45;
        //var lightHelper = new THREE.SpotLightHelper(spotLight);
        //scene.add(lightHelper);
        scene.add(spotLight);
    }

    // manager
    function loadModel() {
        object.traverse(function(child) {
            if (child.isMesh) {
                child.material.color = 0x4c4c4c;
            }
        });

        object.position.y = -150;
        scene.add(object);
    }

    function onProgress(xhr) {
        if (xhr.lengthComputable) {
            const percentComplete = (xhr.loaded / xhr.total) * 100;
            console.log("model " + Math.round(percentComplete, 2) + "% downloaded");
        }
    }

    function onError() {}

    function ForceShadows(object) {
        //traverse SCENE
        scene.traverse(function(child) {
            if (child.isMesh) {
                child.castShadow = true;
                child.receiveShadow = true;
            }
        });
    }

    function FindFirstObject(object) {
        //traverse SCENE
        scene.traverse(function(child) {
            if (child.isMesh) {
                const position = child.geometry.attributes.position;
                const vector = new THREE.Vector3();

                for (let i = 0, l = position.count; i < l; i++) {
                    if (vericesReady === false) {
                        vector.fromBufferAttribute(position, i);
                        vector.applyMatrix4(child.matrixWorld);
                        //console.log(vector);
                        instPosX = vector.x;
                        instPosY = vector.y;
                        instPosZ = vector.z;
                        //console.log("instPosX = " + instPosX);
                        //console.log("instPosY = " + instPosY);
                        //console.log("instPosZ = " + instPosZ);
                        vericesReady = true;
                        //break;
                    }
                }
            }
        });
    }

    function setupMesh() {
        let loader = new OBJLoader();

        //const mat = new THREE.MeshBasicMaterial({
        //color: "grey",
        //opacity: 0.6
        //});

        //const mat = new THREE.MeshPhongMaterial({
        //color: 0x494949,
        //depthWrite: false,
        //opacity: 1.0
        //});

        //try MeshStandardMaterial...
        //const mat = new THREE.MeshPhongMaterial({
        const mat = new THREE.MeshStandardMaterial({
            color: 0x494949,
            side: THREE.DoubleSide,
            depthTest: true,
            depthWrite: true
            //flatShading: false,
            //opacity: 1.0
        });

        let url =
            //"https://s3-us-west-2.amazonaws.com/s.cdpn.io/254249/blender%20monkey.obj";

            //"https://raw.githubusercontent.com/efavreau/models/main/WicksteedLgr3/WinterGardenVU_02_Lgr.obj";

            //"https://raw.githubusercontent.com/efavreau/models/main/HelloTest/HelloTest.obj";
            //"https://raw.githubusercontent.com/efavreau/models/main/WinterGardenVU/WinterGardenVU_02_Lgr.obj";

            //Round_Table
            "https://raw.githubusercontent.com/efavreau/models/main/Round_Table/RoundTable.obj";

        //"https://threejsfundamentals.org/threejs/resources/models/windmill/windmill.obj";
        //"https://raw.githubusercontent.com/bkaradzic/bgfx/master/examples/assets/meshes/bunny.obj";

        loader.load(
            url,
            // called when resource is loaded
            (object) => {
                object.position.y = -150;

                //object.scale.x = allScale;
                //object.scale.y = allScale;
                //object.scale.z = allScale;

                //object.material = mat;

                posY = object.position.y;
                object.position.x = 0;
                posX = object.position.x;
                object.position.z = 0;
                posZ = object.position.z;
                //scene.add(object);
                //getCenter(object);
                //objs.push(object);

                object.traverse(function(child) {
                    if (child.isMesh) {
                        child.material = mat;
                        child.castShadow = true;
                        child.receiveShadow = true;
                        //node.material = mat;//no
                        child.material.side = THREE.DoubleSide;
                        //child.frustumCulled = false; //avoid disappearing
                    }
                });

                //object.traverse(function (child) {
                //if (child.isMesh) {
                //instmesh = new THREE.InstancedMesh(child.geometry, child.material, 1);
                //instmesh.setMatrixAt(0, dummy.matrix);
                //scene.add(instmesh);
                //}
                //});

                //scene.add(instmesh);
                //getCenter(instmesh);
                //objs.push(instmesh);

                scene.add(object);
                getCenter(object);
                //objects.push(object); //array of objects
                //buffobjects.push(object); //objects to export
                //HashtableObjMat(object); //Hashtable Object Name / Material

                //FindFirstObject(object); //test
                //objs.push(object);//dragcontrol
                //zeobj = object;//no
            },
            // called when loading is in progresses
            (xhr) => {
                let amount = Math.round((xhr.loaded / xhr.total) * 100);
                //console.log(`${amount}% loaded`);
            },
            // called when loading has errors
            () => {
                console.log("An error happened");
            }
        );
    }

    function setupObjMtl() {
        const manager = new THREE.LoadingManager();

        manager.onProgress = function(item, loaded, total) {
            console.log(item, loaded, total);
        };

        // texture
        new MTLLoader(manager).setPath(pref).load(mtlLongName, function(materials) {
            materials.preload();

            new OBJLoader(manager)
                .setMaterials(materials)
                .setPath(pref)
                .load(
                    objLongName,
                    function(object) {
                        object.position.y = -150;
                        posY = object.position.y;
                        object.position.x = 0;
                        posX = object.position.x;
                        object.position.z = 0;
                        posZ = object.position.z;

                        scene.add(object);
                        //objects.push(object); //array of objects
                        getCenter(object); //get object center
                        //HashtableObjMat(object); //Hashtable Object Name / Material
                        ForceShadows(object);
                        //FindFirstObject(object);
                    },
                    onProgress,
                    onError
                );
        });
    }

    function getCenter(object) {
        const box = new THREE.BoxHelper(object, 0xffff00);

        var geometry = box.geometry;
        geometry.computeBoundingBox();
        var center = new THREE.Vector3();
        geometry.boundingBox.getCenter(center);
        object.localToWorld(center);

        posX = center.x;
        posY = center.y;
        posZ = center.z;

        controls = new OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.25;
        controls.enableZoom = true;
        controls.target.set(posX, posY + 150, posZ);
        controls.update();
    }

    function draw() {
        requestAnimationFrame(draw);

        var delta = clock.getDelta();
        //update positions
        if (modelReady) {
            for (var i = 0; i < arrmixer.length; i++) {
                arrmixer[i].update(delta); //activate each animation
                arrmodelGroup[i].position.copy(arrboxmesh[i].position);
                arrboxHelper[i].update();
            }
        }
        renderer.render(scene, camera);
    }

    function setupEventListeners() {
        window.addEventListener("resize", onWindowResize);
    }

    function onWindowResize() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }
    </script>
</body>

</html>
