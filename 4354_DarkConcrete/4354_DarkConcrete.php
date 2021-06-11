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
    let allScale = 0.03;
    let rotY = 0;
    let InstPosX = 0;
    let InstPosY = 0;
    let InstPosZ = 0;
    let boxScale = 8 * allScale;
    let startPosY = -150;
    let startBoxMeshScale = 1;
    let HandleScale = 1;
    let posXmem = 0;
    let posYmem = 0;
    let posZmem = 0;
    var mixer;
    var arrmixer = [];
    var model;
    var clock = new THREE.Clock();
    var boxgeometry, boxmaterial, boxmesh, boxHelper;
    let modelGroup;
    var arrmodelGroup = [];
    var arrboxHelper = [];
    var arrboxmesh = [];
    let modelReady = false;
    let spotLight;
    let camhelper;
    let offset = 4;
    let instPosX, instPosY, instPosZ;
    let HandleVisible = true;
    let bmname = 1;
    //gui
    let gui;
    let settings;
    var arranim = [];
    let mygltf;
    var contrFld;
    var gltfFld;
    var modifyFld;
    var expfFld;
    var anim = "Anim";
    var elem = "Name";
    var elemnb = 8;
    //obj
    //let objName = "HelloTest";
    //let objName = "5310_Cabin";
    //let pref = "https://raw.githubusercontent.com/efavreau/models/main/5310_Cabin/";
    //"https://raw.githubusercontent.com/efavreau/models/main/HelloTest/";

let objName = "4354_DarkConcrete";
    let pref = "./";
    let suffobj = ".obj";
    let suffmtl = ".mtl";
    let objLongName = objName + suffobj;
    let mtlLongName = objName + suffmtl;
    let vericesReady = false;
    let firstvector;
    var selectedobjbyid;
    var objects = [];
    var buffobjects = [];
    let object;
    var newmaterial;
    //var oldmaterial;
    var myHash;
    var arrmaterials = [];
    var myHashObjBoxMeshName;
    var myHashObjBoxHelperName;
    var gltfName;
    var clickmodel;
    var clickhelper;

    //test
    let dragControls;

    setup();
    draw();

    function setup() {
        setupScene();
        setupCamera();
        //setupMesh(); //light
        setupObjMtl(); //with textures
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

        //HashTable object name / Material name
        myHash = {}; // New object
        myHashObjBoxMeshName = {}; // New BoxMesh Name / Model Id
        myHashObjBoxHelperName = {}; // New BoxMesh Name / BoxHelper Id

    }

    var raycaster = new THREE.Raycaster(); // Needed for object intersection
    var mouse = new THREE.Vector2(); //Needed for mouse coordinates
    var clr = 0xa0a0a; //couleur emissive

    function onDocumentMouseDown(event) {
        event.preventDefault();

        // var mouse = new THREE.Vector2(); //Needed for mouse coordinates
        mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
        mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
        // update the picking ray with the camera and mouse position
        raycaster.setFromCamera(mouse, camera);

        var intersects = raycaster.intersectObjects(objects, true);

        if (selectedobjbyid !== null) {
            scene.traverse(function(child) {
                if (child.isMesh) {
                    if (child.name === selectedobjbyid) {
                        // console.log("object name = " + object.name); //OK

                        if (selectedobjbyid.includes("boxmesh")) {
                            child.material = boxmaterial;
                            //model = myHashObjBoxMeshName[child.name]; // returns model
                            //clickmodel = myHashObjBoxMeshName[child.name];
                            //console.log("child.name = " + myHashObjBoxMeshName[child.name].name); //not OK
                        } else {
                            var matname = myHash[child.uuid]; // returns material name
                            // console.log("matname = " + matname);

                            for (var i = 0; i < arrmaterials.length; i++) {
                                if (arrmaterials[i].name == matname) {
                                    // console.log("ze material = " + arrmaterials[i].name); //OK
                                    child.material = arrmaterials[i];
                                    break;
                                }
                            } // OK!
                        }
                    }
                }
            });
        }

        //document.getElementById("pp").innerHTML = "";

        //ok
        if (intersects.length > 0) {
            object = intersects[0].object;

            //traverse SCENE to find objects with this object name
            scene.traverse(function(child) {
                if (child.isMesh) {
                    // console.log("child name = " + child.name);

                    if (child.name === object.name) {
                        //console.log("object name = " + object.name); //OK

                        if (child.name.includes("boxmesh")) {
                            newmaterial.transparent = true;
                            boxmesh = child; //
                            //isclicked = true;
                            //console.log("isclicked = " + isclicked);

                            clickmodel = myHashObjBoxMeshName[child.name];
                            clickhelper = myHashObjBoxHelperName[child.name];
                            //myHashObjBoxHelperName
                            //console.log("clickmodel = " + clickmodel);

                            if (clickmodel !== undefined) {
                                scene.traverse(function(child) {
                                    if (child.isGroup) {
                                        //console.log("clickmodel = " + clickmodel);
                                        //console.log(clickmodel + " = " + child.name);
                                        if (child.id !== null) {
                                            if (child.id !== "") {
                                                //console.log(clickmodel + " = " + child.id);
                                                //console.log(clickmodel);
                                                //console.log(child.id);
                                                if (clickmodel.toString() === child.id.toString()) {
                                                    //console.log("OK = " + child.id);
                                                    model = child;
                                                    elem = model.name;
                                                    var controller = modifyFld.__controllers[
                                                        elemnb]; //get Element
                                                    controller.remove();
                                                    //console.log("OK elem = " + elem);
                                                    modifyFld.add(settings, "Element", elem).setValue(
                                                        elem);
                                                }
                                            }
                                        }
                                    }
                                });
                            }

                            if (clickhelper !== undefined) {
                                scene.traverse(function(child) {
                                    //if (child.isMesh) {
                                    if (child.id !== null) {
                                        if (child.id !== "") {
                                            //console.log(clickhelper + " = " + child.id);
                                            //console.log(clickmodel);
                                            //console.log(child.id);
                                            if (clickhelper.toString() === child.id.toString()) {
                                                //console.log(clickhelper + " = " + child.id);
                                                boxHelper = child;
                                            }
                                        }
                                    }
                                    //}
                                });
                            }
                        } else {
                            newmaterial.transparent = false;

                            elem = object.name;
                            var controller = modifyFld.__controllers[elemnb]; //get Element
                            controller.remove();
                            //console.log("OK elem = " + elem);
                            modifyFld.add(settings, "Element", elem).setValue(elem);
                        }

                        child.material = newmaterial;
                        selectedobjbyid = object.name;

                        //document.getElementById("pp").innerHTML = object.name;
                    }

                    //if (clickmodel !== undefined) {
                    //console.log("clickmodel = " + clickmodel);
                    //console.log("child.name = " + child.name);
                    //var findmodel = scene.getObjectById(clickmodel, true); // returns model

                    //if (child.name === clickmodel) {
                    //console.log("child.name = " + child.name);
                    // model = child;
                    //}

                    //}
                }
            });
        }

        //gris 0x707070
        //gris pale 0xaaaaaa
        //vert 0x31c400
        //bleu 0x42c4
    }

    function setupInstanceMeshGltf() {
        //const group = new THREE.Group();

        //objs.length = 0;//annule array

        //group = new THREE.Group();
        //scene.add(group);

        var groupNB = 0;

        var loader = new GLTFLoader();

        // Optional: Provide a DRACOLoader instance to decode compressed mesh data
        const dracoLoader = new DRACOLoader();
        dracoLoader.setDecoderPath(
            "https://raw.githubusercontent.com/mrdoob/three.js/dev/examples/js/libs/draco/"
        );

        // Optional: Pre-fetch Draco WASM/JS module.
        //dracoLoader.preload();

        loader.setDRACOLoader(dracoLoader);

        const mat = new THREE.MeshBasicMaterial({
            color: "grey",
            opacity: 0.6
        });

        loader.load(urlGltf, function(gltf) {
            gltf.scene.traverse(function(node) {
                //grouping for dragcontrol
                if (node instanceof THREE.Group) {
                    modelGroup = node;
                    if (groupNB == 0) {
                        arrmodelGroup.push(modelGroup);
                    }
                    groupNB++;
                    //console.log("arrmodelGroup =  " + modelGroup.name);//problem here
                    //console.log("groupNB =  " + groupNB);
                }

                if (node instanceof THREE.Mesh) {
                    node.castShadow = true;
                    node.receiveShadow = true;
                    node.material.side = THREE.DoubleSide;
                    node.frustumCulled = false; //avoid disappearing
                    //instmesh = new THREE.InstancedMesh(node.geometry, node.material, 1);
                    //instmesh.setMatrixAt(0, dummy.matrix);
                }
            });

            mygltf = gltf;
            model = gltf.scene;
            //ajout gltfName
            model.name = "model" + bmname;

            //group = new THREE.Group();
            //scene.add(group);

            //group.add( model );
            //objs.push(group);//no
            //console.log("model =  " + model.children.length);

            //GroupNodes(mygltf);

            //objs.length = 0;
            //objs.push(model);//separe

            model.position.y = startPosY; //ou
            //model.position.x = posX;
            //model.position.y = posY;
            //model.position.z = posZ;

            arranim = [];
            //console.log(gltf.animations.length);

            for (var i = 0; i < gltf.animations.length; i++) {
                //push arranim
                if (gltf.animations[i] != null) {
                    arranim.push(gltf.animations[i].name);
                }
            }

            scene.add(model);

            //Adjust scale
            model.scale.x = allScale;
            model.scale.y = allScale;
            model.scale.z = allScale;

            mixer = new THREE.AnimationMixer(model);

            for (var i = 0; i < gltf.animations.length; i++) {
                if (gltf.animations[i] != null) {
                    mixer.clipAction(gltf.animations[i]).play();
                    break; //stop at firts animation
                }
            }
            arrmixer.push(mixer); //array of mixer

            boxmaterial = new THREE.MeshBasicMaterial({
                color: "blue",
                wireframe: true,
                transparent: true,
                opacity: 0.0
            });

            //const zbox = new THREE.Box3().setFromObject(model);
            //const trgt = new THREE.Vector3();
            //zbox.getSize(trgt); // pass in size so a new Vector3 is not allocated

            boxmesh = new THREE.Mesh(new THREE.BoxGeometry(1.5, 3.0, 1.5), boxmaterial);
            boxmesh.name = "boxmesh" + bmname;
            //console.log("boxmesh =  " + boxmesh.name);
            bmname++;

            boxmesh.scale.x = startBoxMeshScale + allScale;
            boxmesh.scale.y = startBoxMeshScale + allScale;
            boxmesh.scale.z = startBoxMeshScale + allScale;

            boxmesh.position.set(0, -150, 0);
            boxmesh.geometry.translate(0, 0.65, 0);
            scene.add(boxmesh);
            objs.push(boxmesh);
            arrboxmesh.push(boxmesh);

            objects.push(boxmesh); //array of objects
            //HashtableObjMat(boxmesh); //Hashtable Object Name / Material

            buffobjects.push(model); //export

            //myHash[boxmesh.name] = [boxmesh.material.name];
            //console.log("model.id = " + model.id);
            myHashObjBoxMeshName[boxmesh.name] = [model.id];

            //var test = myHashObjBoxMeshName[boxmesh.name];
            //console.log("myHashObjBoxMeshName = " + test);

            //if (groupNB == 1)
            //{
            //console.log("groupNB =  " + groupNB);
            //objs.push(boxmesh);
            //}

            //if(groupNB == 1)
            //{
            //dragControls.transformGroup = false;
            //console.log("groupNB =  " + groupNB);
            //objs.push(boxmesh);
            //}

            //if(groupNB > 1)
            //{
            //group = new THREE.Group();
            //group.attach(model);
            //group.attach(boxmesh);
            //scene.add(group)
            //objs.length = 0
            //objs.push(group);
            //objs.push(model);
            //objs.push(boxmesh);

            //dragControls.transformGroup = true;
            //}

            //console.debug("objs.length = " + objs.length);
            //for (var i = 0; i < objs.length; i++) {
            //console.debug(objs.length);
            //}

            boxHelper = new THREE.BoxHelper(boxmesh, 0xffff00);
            boxHelper.visible = HandleVisible; //ON OFF
            boxHelper.name = "boxhelper" + bmname;
            myHashObjBoxHelperName[boxmesh.name] = [boxHelper.id];

            arrboxHelper.push(boxHelper);
            scene.add(boxHelper);
            //scene.add(model);

            if (arranim.length > 0) {
                var controller = gltfFld.__controllers[2]; //get anim
                controller.remove();

                gltfFld
                    .add(settings, anim, arranim)
                    .setValue(arranim[0])
                    .onChange(changeAnim);
            }

            //group.attach(boxmesh);
            //group.attach(model);
            //objs.push(group);

            modelReady = true;
            //console.log("modelReady = " + modelReady);
        });
    }

    function setupAddInstance() {
        const geometry = new THREE.BoxGeometry(10, 10, 10);
        const material = new THREE.MeshBasicMaterial({
            color: "blue",
            wireframe: true,
            transparent: true,
            opacity: 0.6
        });
        const mesh = new THREE.Mesh(geometry, material, count);
        mesh.position.set(4, -150, 0);
        //mesh.position.x = 100
        mesh.name = "zmesh";
        console.log("OK add instance  = " + mesh.name);
        scene.add(mesh);
        objs.push(mesh);

        //scene.add(instmesh);
        //objs.push(instmesh);

        setupInstanceMesh();
    }

    function setupDragControl() {
        // dat.gui controls
        //const settings = { OrbitControl: true, Drag: true, add: setupAddInstance };
        settings = {
            OrbitControl: true,
            Drag: true,
            AddInstance: setupInstanceMeshGltf,
            Url: "",
            Element: elem,
            Scale: allScale,
            Handle: HandleVisible,
            HandleScale: HandleScale,
            RotationY: rotY,
            PositionX: InstPosX,
            PositionY: InstPosY,
            PositionZ: InstPosZ,
            Anim: "anim 1",
            Delete: DeleteElement,
            ExportGLTF: exportGLTF
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

        gltfFld = gui.addFolder("Gltf");
        //Add gltf
        gltfFld.add(settings, "Url").onFinishChange(function(value) {
            //Do something with the new value
            //console.log(value);
            urlGltf = value;
            //console.log("urlGltf = " + urlGltf);
        });
        gltfFld.add(settings, "AddInstance");

        //Animation list
        gltfFld.add(settings, anim, arranim).onChange(changeAnim);

        gltfFld.open();

        modifyFld = gui.addFolder("Modify");
        //Model scale
        //modifyFld.add(settings, "Scale").onChange(modifyScale);

        modifyFld
            .add(settings, "Scale", 0, 20)
            .name("Scale")
            .listen()
            .onChange(modifyScale);
        modifyFld
            .add(settings, "Handle")
            .name("Handle")
            .listen()
            .onChange(modifyHandle);
        modifyFld
            .add(settings, "HandleScale", -20, 20)
            .name("HandleScale")
            .listen()
            .onChange(modifyHandleScale);
        modifyFld
            .add(settings, "RotationY", -180, 180)
            .name("RotationY")
            .listen()
            .onChange(modifyRotation)
            .onFinishChange(modifyPosEnd);

        modifyFld
            .add(settings, "PositionX", -20, 20)
            .name("PositionX")
            .listen()
            .onChange(modifyPosX)
            .onFinishChange(modifyPosEnd);

        modifyFld
            .add(settings, "PositionY", -20, 20)
            .name("PositionY")
            .listen()
            .onChange(modifyPosY)
            .onFinishChange(modifyPosEnd);

        modifyFld
            .add(settings, "PositionZ", -20, 20)
            .name("PositionZ")
            .listen()
            .onChange(modifyPosZ)
            .onFinishChange(modifyPosEnd);

        modifyFld.add(settings, "Delete");

        modifyFld.add(settings, "Element", elem);

        modifyFld.open();

        expfFld = gui.addFolder("Export");
        expfFld.add(settings, "ExportGLTF");
        expfFld.open();

        document.body.appendChild(renderer.domElement);
    }

    function exportGLTF() {

        scene.traverse(function(child) {
            if (child.isMesh) {
                child.position.y = 150;//initial move
            }
        });

        // Instantiate a exporter
        const exporter = new GLTFExporter();

        const options = {
            trs: true, //transp
            onlyVisible: true,
            truncateDrawRange: true,
            binary: true, //.glb
            maxTextureSize: 4096, // To prevent NaN value
            //animations: testanim, //ok for model
            forceIndices: false
        };

        // Parse the input and generate the glTF output
        //exporter.parse(
        //scene,
        //function (gltf) {
        //console.log(gltf);
        //downloadJSON(gltf);
        //},
        // options
        //);

        console.log(buffobjects.length + " buffobjects");

        exporter.parse(
            buffobjects,
            function(result) {
                if (result instanceof ArrayBuffer) {
                    //console.log("saveArrayBuffer");
                    saveArrayBuffer(result, "scene.glb");
                } else {
                    //console.log("saveString");
                    const output = JSON.stringify(result, null, 2);
                    console.log(output);
                    saveString(output, "scene.gltf");
                }
            },
            options
        );

        //console.log("Ok end");

        //reset to 0
        scene.traverse(function(child) {
            if (child.isMesh) {
                child.position.y = 0;
            }
        });
    }

    const link = document.createElement("a");
    link.style.display = "none";
    //document.body.appendChild(link); // Firefox workaround - Chrome ok

    function save(blob, filename) {
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();

        //console.log("save = " + filename);
        console.log("link = " + link);

        // URL.revokeObjectURL( url ); breaks Firefox...
    }

    function saveString(text, filename) {
        save(new Blob([text], {
            type: "text/plain"
        }), filename);
    }

    function saveArrayBuffer(buffer, filename) {
        save(new Blob([buffer], {
            type: "application/octet-stream"
        }), filename);
        //console.log("filename = " + filename);
    }

    function DeleteElement() {
        if (modelReady) {
            if (model !== null) {
                scene.remove(model);
                scene.remove(boxmesh);
                scene.remove(boxHelper);

                //buffobjects
                const index = buffobjects.indexOf(model);
                if (index > -1) {
                    buffobjects.splice(index, 1);
                }
            }
        }
    }

    function modifyHandle(value) {
        HandleVisible = value;
        if (modelReady) {
            if (model !== null) {
                //boxHelper.visible = HandleVisible;
                //or
                //boxmaterial.transparent = HandleVisible;

                for (var i = 0; i < arrboxHelper.length; i++) {
                    if (arrboxHelper[i] !== null) {
                        arrboxHelper[i].visible = HandleVisible;
                    }
                }
            }
        }
    }

    function modifyScale(value) {
        //ajout...
        allScale = value;
        //console.log("OK startBoxmeshX = " + startBoxmeshX);
        if (modelReady) {
            if (model !== null) {
                model.scale.x = allScale;
                model.scale.y = allScale;
                model.scale.z = allScale;
                //console.log("OK allScale1 = " + allScale);
                boxmesh.scale.x = allScale + HandleScale;
                boxmesh.scale.y = allScale + HandleScale;
                boxmesh.scale.z = allScale + HandleScale;
                //console.log("OK allScale2 = " + allScale);
            }
        }
    }

    //BoxMeshScale
    function modifyHandleScale(value) {
        HandleScale = value;
        if (modelReady) {
            if (model !== null) {
                boxmesh.scale.x = allScale + HandleScale;
                boxmesh.scale.y = allScale + HandleScale;
                boxmesh.scale.z = allScale + HandleScale;
            }
        }
    }

    function modifyPosX(value) {
        InstPosX = value;
        if (modelReady) {
            if (model !== null) {
                //console.log("OK InstPosX = " + InstPosX);
                //model.position.x = InstPosX;
                //model.position.x = posXmem + InstPosX;
                boxmesh.position.x = posXmem + InstPosX; //boxmesh and model are grouped
                //console.log("OK model.position.x  = " + model.position.x );

                //ajuster comme scale...
            }
        }
    }

    function modifyPosY(value) {
        InstPosY = value;
        if (modelReady) {
            if (model !== null) {
                //model.position.y = posYmem + InstPosY;
                boxmesh.position.y = posYmem + InstPosY;
            }
        }
    }

    function modifyPosZ(value) {
        InstPosZ = value;
        if (modelReady) {
            if (model !== null) {
                //model.position.z = posZmem + InstPosZ;
                boxmesh.position.z = posZmem + InstPosZ;
            }
        }
    }

    function modifyPosEnd() {
        posXmem = boxmesh.position.x;
        posYmem = boxmesh.position.y;
        posZmem = boxmesh.position.z;

        //ajouter option si plusieurs mesh
        //model.position.x = posXmem;
        //model.position.y = posYmem;
        //model.position.z = posZmem;
    }

    var de2ra = function(degree) {
        return degree * (Math.PI / 180);
    };

    function modifyRotation(value) {
        rotY = de2ra(value);
        if (modelReady) {
            if (model !== null) {
                model.rotation.y = rotY;
                //boxmesh.rotation.y = rotY;//no
                //boxHelper = new THREE.BoxHelper(boxmesh, 0xffff00);
            }
        }
    }

    function changeAnim(value) {
        //console.log("value:" + value);

        arranim = [];

        for (var i = 0; i < mygltf.animations.length; i++) {
            if (mygltf.animations[i] != null) {
                if (mygltf.animations[i].name !== value) {
                    mixer.clipAction(mygltf.animations[i]).stop();
                }
            }
        }

        for (var i = 0; i < mygltf.animations.length; i++) {
            if (mygltf.animations[i] != null) {
                if (mygltf.animations[i].name === value) {
                    mixer.clipAction(mygltf.animations[i]).play();
                    break;
                }
            }
        }
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

        window.addEventListener("click", onDocumentMouseDown, false);
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
                objects.push(object); //array of objects
                buffobjects.push(object); //objects to export
                HashtableObjMat(object); //Hashtable Object Name / Material

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

                        objects.push(object); //array of objects
                        buffobjects.push(object); //objects to export
                        HashtableObjMat(object); //Hashtable Object Name / Material

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

    function HashtableObjMat(object) {
        //traverse SCENE
        scene.traverse(function(child) {
            if (child.isMesh) {
                myHash[child.uuid] = [child.material.name];
                arrmaterials.push(child.material);
            }
        });
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
