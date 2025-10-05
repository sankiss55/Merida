@section('title','Tamaño del Establecimiento')
<style>
@media only screen and (max-width: 767px) {
    .mobile-version {
        margin-top: 500px !important;
    }

}
</style>
<div class="h-full overflow-y-auto p-2">
    <div class="flex flex-wrap md:flex-nowrap md:space-x-2 h-full">
        <div class="order-2 md:order-1 w-full md:w-2/3 h-full flex space-x-2 pb-2 ">
            <div class="border w-full md:h-[100%]  bg-white rounded-lg shadow" id="mainMap" wire:ignore></div>
        </div>
        <div class="order-1 md:order-2 w-full md:w-1/3 flex flex-wrap">
            <div class="w-full  py-2 flex flex-wrap justify-center items-center">
                <div class="w-full">
                    <img class="w-1/3 h-auto mx-auto" src="{{ asset('img/inegi.png') }}" alt="">
                </div>
                <div class="w-full text-center font-semibold bg-white rounded-lg shadow py-2 max-h-[7vh]">
                    <span  id="total">0</span> Registros
                </div>
                <div class="w-full bg-white rounded-lg shadow-lg py-2 min-h-[30vh] flex items-center">
                    <canvas id="chart" class="w-full h-full" style="height:230px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap md:flex-nowrap md:space-x-2 mobile-version">
            <div class="w-full md:w-1/3 bg-gray-100 p-4">
                <div class="flex flex-wrap md:flex-nowrap md:space-x-2">
                    <div class="w-1/8 md:w-auto justify-center p-2">
                        <label for="" class="font-semibold text-center pt-2">Alojamiento temporal:</label>
                    </div>
                    <div class="w-1/8 md:w-auto flex justify-center p-2 md:space-x-2">
                        <button onclick="deseleccionarTodas('ramactividadList')" class="py-1 px-4 rounded-lg shadow hover:shadow-lg bg-blue-600 hover:bg-blue-500 text-white cursor-pointer">
                            <i class="fas fa-times"></i>
                        </button>
                        <button onclick="seleccionarTodas('ramactividadList')" class="py-1 px-4 rounded-lg shadow hover:shadow-lg bg-blue-600 hover:bg-blue-500 text-white cursor-pointer">
                            <i class="fas fa-check-square"></i>
                        </button>
                    </div>
                </div>
                <div class="w-full bg-white rounded-lg shadow" style="max-height: 200px; overflow-y: auto; padding-left: 35px;">
                    <div class="checkbox-list text-left" id="ramactividadList">
                        <!-- Contenido de la lista de actividades -->
                        <div class="checkbox-item">
                            <input type="checkbox" id="actividadesp_0" name="ramaactividad[]" value="7211">
                            Hoteles, moteles y similares
                        </div>
                    
                        <div class="checkbox-item">
                            <input type="checkbox" id="actividadesp_1" name="ramaactividad[]" value="7212">
                            Campamentos y albergues recreativos
                        </div>
                    
                        <div class="checkbox-item">
                            <input type="checkbox" id="actividadesp_2" name="ramaactividad[]" value="7213">
                            Pensiones y casas de huéspedes, y departamentos y casas amueblados con servicios de hotelería
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="actividadesp_3" name="ramaactividad[]" value="7223">
                            Servicios de preparación de alimentos por encargo    
                        </div>
                    
                        <div class="checkbox-item">
                            <input type="checkbox" id="actividadesp_4" name="ramaactividad[]" value="7224">
                            Centros nocturnos, bares, cantinas y similares
                        </div>
                    
                        <div class="checkbox-item">
                            <input type="checkbox" id="actividadesp_5" name="ramaactividad[]" value="7225">
                            Servicios de preparación de alimentos y bebidas alcohólicas y no alcohólicas
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="actividadesp_6" name="ramaactividad[]" value="6221">
                            Hospitales generales
                        </div>
                    
                        <div class="checkbox-item">
                            <input type="checkbox" id="actividadesp_7" name="ramaactividad[]" value="6222">
                            Hospitales psiquiátricos y para el tratamiento por adicción
                        </div>
                    
                        <div class="checkbox-item">
                            <input type="checkbox" id="actividadesp_8" name="ramaactividad[]" value="6223">
                            Hospitales de otras especialidades médicas
                        </div>

                    </div>
                </div>
            </div>
            <div class="w-full md:w-1/3 bg-gray-100 p-4">
                <div class="flex flex-wrap md:flex-nowrap md:space-x-2">    
                    <div class="w-1/8 md:w-auto justify-center p-2">
                        <label for="" class="font-semibold text-center pt-2">Colonias:</label>
                    </div>
                    <div class="w-1/8 md:w-auto flex justify-center p-2 md:space-x-2">
                        <button onclick="deseleccionarTodas('coloniasList')" class="py-1 px-4 rounded-lg shadow hover:shadow-lg bg-blue-600 hover:bg-blue-500 text-white cursor-pointer">
                            <i class="fas fa-times"></i>
                        </button>
                        <button onclick="seleccionarTodas('coloniasList')" class="py-1 px-4 rounded-lg shadow hover:shadow-lg bg-blue-600 hover:bg-blue-500 text-white cursor-pointer">
                            <i class="fas fa-check-square"></i>
                        </button>
                    </div>
                </div>
                <div class="w-full bg-white rounded-lg shadow" style="max-height: 200px; overflow-y: auto; padding-left: 35px;">
                    <div class="checkbox-list text-left" id="coloniasList">
                        <!-- Contenido de la lista de colonias -->
                    </div>
                </div>
            </div>
            <div class="w-full md:w-1/3 bg-gray-100 p-4">
                <div class="w-full text-center bg-white rounded-lg shadow py-2" style="margin-bottom: 10px;">
                    <label for="" class="font-semibold pr-2">Poligono: </label>
                    <select class="w-auto border border-gray-100 rounded-lg" name="" id="areaList">
                        <option value="0"> MERIDA </option>
                        @foreach($poligons as $polygon)
                            <option value="{{ $polygon->id }}">{{ $polygon->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full text-center bg-white rounded-lg shadow py-2">
                    <label for="" class="font-semibold pr-2">Estrato: </label>
                    <select class="w-auto border border-gray-100 rounded-lg" name="" id="estratoList">
                        <option value=""> Todos </option>
                        <option value="0 a 5 personas">0 a 5 personas</option>
                        <option value="6 a 10 personas">6 a 10 personas</option>
                        <option value="11 a 30 personas">11 a 30 personas</option>
                        <option value="31 a 50 personas">31 a 50 personas</option>
                        <option value="51 a 100 personas">51 a 100 personas</option>
                        <option value="101 a 250 personas">101 a 250 personas</option>
                        <!-- <option value="251 y más personas">251 y más personas</option> -->
                    </select>
                </div>
                <div class="w-full text-center bg-white rounded-lg shadow py-2" style="margin-top: 10px;">
                    <label for="" class="font-semibold pr-2">Vialidad: </label>
                    <select class="w-auto border border-gray-100 rounded-lg" name="" id="vialidadList">
                        <option value="0"> Todas </option>
                        <option value="ANDADOR">ANDADOR</option>
                        <option value="AUTOPISTA">AUTOPISTA</option>
                        <option value="AVENIDA">AVENIDA</option>
                        <option value="BOULEVARD">BOULEVARD</option>
                        <option value="CALLE">CALLE</option>
                        <option value="CALLEJON">CALLEJON</option>
                        <option value="CARRETERA">CARRETERA</option>
                        <option value="CERRADA">CERRADA</option>
                        <option value="CIRCUITO">CIRCUITO</option>
                        <option value="CONTINUACION">CONTINUACION</option>
                        <option value="CORREDOR">CORREDOR</option>
                        <option value="DIAGONAL">DIAGONAL</option>
                        <option value="OTRO (ESPECIFIQUE)">OTRO</option>
                        <option value="PASAJE">PASAJE</option>
                        <option value="PERIFERICO">PERIFERICO</option>
                        <option value="PROLONGACION">PROLONGACION</option>
                        <option value="PRIVADA">PRIVADA</option>
                        <option value="RETORNO">RETORNO</option>
                        <option value="VIADUCTO">VIADUCTO</option>
                    </select>
                </div>
            </div>
            <div class="w-full md:w-1/3 bg-gray-100 p-4">
                <div class="w-full text-center bg-white rounded-lg shadow py-2" style="margin-bottom: 10px;">
                    <label for="" class="font-semibold pr-2">Asentamiento: </label>
                    <select class="w-auto border border-gray-100 rounded-lg" name="" id="asentamientoList">
                        <option value=""> Todas</option>
                        <option value="AEROPUERTO">AEROPUERTO</option>
                        <option value="AMPLIACION">AMPLIACION</option>
                        <option value="BARRIO">BARRIO</option>
                        <option value="CIUDAD">CIUDAD</option>
                        <option value="CIUDAD INDUSTRIAL">CIUDAD INDUSTRIAL</option>
                        <option value="COLONIA">COLONIA</option>
                        <option value="CONDOMINIO">CONDOMINIO</option>
                        <option value="CONJUNTO HABITACIONAL">CONJUNTO HABITACIONAL</option>
                        <option value="CUARTEL">CUARTEL</option>
                        <option value="EJIDO">EJIDO</option>
                        <option value="EXHACIENDA">EXHACIENDA</option>
                        <option value="FRACCION">FRACCION</option>
                        <option value="FRACCIONAMIENTO">FRACCIONAMIENTO</option>
                        <option value="GRANJA">GRANJA</option>
                        <option value="HACIENDA">HACIENDA</option>
                        <option value="LOCALIDAD">LOCALIDAD</option>
                        <option value="MANZANA">MANZANA</option>
                        <option value="NINGUNO">NINGUNO</option>
                        <option value="OTRO (ESPECIFIQUE)">OTRO</option>
                        <option value="PARQUE INDUSTRIAL">PARQUE INDUSTRIAL</option>
                        <option value="PRIVADA">PRIVADA</option>
                        <option value="PROLONGACION">PROLONGACION</option>
                        <option value="PUEBLO">PUEBLO</option>
                        <option value="PUERTO">PUERTO</option>
                        <option value="RANCHERIA">RANCHERIA</option>
                        <option value="REGION">REGION</option>
                        <option value="RESIDENCIAL">RESIDENCIAL</option>
                        <option value="RINCONADA">RINCONADA</option>
                        <option value="SECCION">SECCION</option>
                        <option value="SUPERMANZANA">SUPERMANZANA</option>
                        <option value="UNIDAD">UNIDAD</option>
                        <option value="UNIDAD HABITACIONAL">UNIDAD HABITACIONAL</option>
                        <option value="VILLA">VILLA</option>
                        <option value="ZONA COMERCIAL">ZONA COMERCIAL</option>
                        <option value="ZONA FEDERAL">ZONA FEDERAL</option>
                        <option value="ZONA INDUSTRIAL">ZONA INDUSTRIAL</option>
                        <option value="ZONA NAVAL">ZONA NAVAL</option>
                    </select>
                </div>
                <div class="w-full flex justify-center p-2">
                    <button onclick="searchStratum()" class="py-2 px-4 rounded-lg shadow hover:shadow-lg bg-blue-600 hover:bg-blue-500 text-white cursor-pointer">
                        <i class="fas fa-search icon"></i> Buscar
                    </button>
                </div>
                <div class="w-full flex justify-center p-2">
                    <button onclick="exportChart()" class="py-2 px-4 rounded-lg shadow hover:shadow-lg bg-blue-600 hover:bg-blue-500 text-white cursor-pointer">
                        <i class="fas fa-file-download"></i> Exportar
                    </button>
                </div>
            </div>
    </div>
    <div class="flex flex-wrap md:flex-nowrap md:space-x-2 mobile-version">
            <div class="w-full md:w-1/3 bg-gray-100 p-4">
                <div class="flex flex-wrap md:flex-nowrap md:space-x-2">
                    <div class="w-1/8 md:w-auto justify-center p-2">
                        <label for="" class="font-semibold text-center pt-2">Actividad:</label>
                    </div>
                    <div class="w-1/8 md:w-auto flex justify-center p-2 md:space-x-2">
                        <button onclick="deseleccionarTodas('actividadList')" class="py-1 px-4 rounded-lg shadow hover:shadow-lg bg-blue-600 hover:bg-blue-500 text-white cursor-pointer">
                            <i class="fas fa-times"></i>
                        </button>
                        <button onclick="seleccionarTodas('actividadList')" class="py-1 px-4 rounded-lg shadow hover:shadow-lg bg-blue-600 hover:bg-blue-500 text-white cursor-pointer">
                            <i class="fas fa-check-square"></i>
                        </button>
                    </div>
                </div>
                <div class="w-full bg-white rounded-lg shadow" style="max-height: 200px; overflow-y: auto; padding-left: 35px;">
                    <div class="checkbox-list text-left" id="actividadList">
                        <!-- Contenido de la lista de actividades -->
                    </div>
                </div>
            </div>
    </div>
</div>
        
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCe3fVMFtZ9tjqzfIviCztwC5WUn0S5iYM"></script>
<script src="{{ asset('js/areas.js') }}"></script>
<script>
    const maparea={
        lat:20.978847994538597,
        lng:-89.61960884983415
    };
    let areas=[];
    let stratum=[];
    let chart;
    let strAreaSelected=' MERIDA ';
    function exportChart()
    {
        exportCanva(document.getElementById('chart'));
    }
    function BIM_pushCompanies(arrcomp) {
        for (let i = 0; i < arrcomp.length; i++) {
            let lat = parseFloat(arrcomp[i].Latitud);
            let lng = parseFloat(arrcomp[i].Longitud);

            let newMarker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                icon: setPinStratum(arrcomp[i].Estrato),
                map: map,
                title: arrcomp[i].name,
            });

            // Agregar evento mouseover para mostrar infowindow
            // newMarker.addListener("mouseover", () => {
            //     if (!newMarker.infowindow) {
            //         newMarker.infowindow = new google.maps.InfoWindow({
            //             content: arrcomp[i].name,
            //         });
            //     }
            //     newMarker.infowindow.open(map, newMarker);
            // });

            // Agregar evento mouseout para cerrar infowindow
            // newMarker.addListener("mouseout", () => {
            //     if (newMarker.infowindow) {
            //         newMarker.infowindow.close();
            //     }
            // });

            mapMarkers.push(newMarker);
        }
    }
    function BIM_clearMarkers(){
        for(let i=0; i < mapMarkers.length; i++) {
            mapMarkers[i].setMap(null)
        }
    }
    function BIM_mainMap(center){
        map = new google.maps.Map(document.getElementById("mainMap"), {
            center: new google.maps.LatLng(center.lat,center.lng),
            zoom: 12,
        })
        infowindow = new google.maps.InfoWindow();
    }
    function drawChart(){
        const ctx=document.getElementById('chart').getContext('2d');
        if(chart) chart.destroy();
        let labels =[], sets=[];
        stratum.map(item=>{
            if(item.estrato != null){
                labels.push(decodeUnicode(item.estrato));
                sets.push(item.total);
            }
        });
        let rgb=rndBgColor(stratum)
        ConfigHorizontalBars.data= {
            labels: labels,
            datasets: [{
                axis: 'y',
                data: sets,
                fill: false,
                backgroundColor: rgb,
                borderWidth: 1
            }]
        };
        ConfigHorizontalBars.options.plugins.legend.display=false;
        ConfigHorizontalBars.options.plugins.title={display: true,text:' Tamaño del Establecimiento ' + strAreaSelected,font:{size:12}};
        chart= new Chart(ctx, ConfigHorizontalBars);
    }
    async function BIM_getStratum() {
        let URL =window.location.protocol + "//" + window.location.host+ "/api/v1/maps/inegi/stratum?api_key="+Api_key;
        let colonias = getDataList("coloniasList");
        let actividades = getDataList("actividadList");
        let rama = getDataList("ramactividadList");
        let areas_1 =  BIM_getelement('areaList')
        let estrato = BIM_getelement('estratoList');
        let vialidad = BIM_getelement('vialidadList')
        let asentamiento = BIM_getelement('asentamientoList')
        const requestBody = {};
        if (areas_1) {
            requestBody.areas = areas_1;
        }
        if (colonias.length > 0) {
            requestBody.colonia = colonias;
        }
        if (estrato.length > 0) {
            requestBody.estrato = estrato
        }
        if (actividades.length > 0) {
            requestBody.searcheconomyactivity = actividades
        }
        if (rama.length > 0) {
            requestBody.searchrama = rama
        }
        if (vialidad.length > 0) {
            requestBody.vialidad = vialidad
        }
        if (asentamiento.length > 0) {
            requestBody.asentamiento = asentamiento
        }

        const response = await fetch(URL,{
            method:'POST',
            body: JSON.stringify(requestBody),
            headers: {"Content-type": "application/json;charset=UTF-8"}
        });

        if (response.status===200) {
            const result = await response.json();
            if (result.body!==null) {
                stratum = result.stratum;
                drawChart();
            } else {
                if(document.getElementById('alert'))document.getElementById('alert').innerHTML='Sin Datos';
            }
        }else{
            console.log("No hay registros")
        }
    }
    async function BIM_getCompanies() { 
        //Filtros
        let colonias = getDataList("coloniasList");
        let actividades = getDataList("actividadList");
        let rama = getDataList("ramactividadList");
        let areas_1 =  BIM_getelement('areaList');
        let estrato = BIM_getelement('estratoList');
        let vialidad = BIM_getelement('vialidadList')
        let asentamiento = BIM_getelement('asentamientoList')
        const requestBody = {};
        if (areas_1) {
            requestBody.areas = areas_1;
        }
        if (colonias.length > 0) {
            requestBody.colonia = colonias;
        }
        if (estrato.length > 0) {
            requestBody.estrato = estrato
        }
        if (actividades.length > 0) {
            requestBody.searcheconomyactivity = actividades
        }
        if (rama.length > 0) {
            requestBody.searchrama = rama
        }
        if (vialidad.length > 0) {
            requestBody.vialidad = vialidad
        }
        if (asentamiento.length > 0) {
            requestBody.asentamiento = asentamiento
        }
        // requestBody.searcheconomyactivity = ""
        const response = await fetch(host+ "/api/v1/maps/inegi/companies?api_key="+Api_key,{
            method:'POST',
            body: JSON.stringify(requestBody),
            headers: {"Content-type": "application/json;charset=UTF-8"}
        });

        if (response.status===200) {
            const result = await response.json();
            if (result.body!==null) {
                Companies = result.companies;
                if(document.getElementById('total'))document.getElementById('total').innerHTML=Companies.length.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                BIM_pushCompanies(Companies);
            } else {
                if(document.getElementById('alert'))document.getElementById('alert').innerHTML='Sin Datos';
            }
        }else{
            alert("No hay registros para el filtro aplicado")
            BIM_clearMarkers();
            if(chart) chart.destroy();
            if(document.getElementById('total'))document.getElementById('total').innerHTML="0";

        }
    }
    function obtenerValorSelect() {
        const selectElement = document.getElementById('areaList');
        const valorSeleccionado = selectElement.value;
        return valorSeleccionado;
    }
    function searchStratum() {
        getAreas();
        BIM_clearMarkers();
        BIM_getCompanies();
        BIM_getStratum();
    }
    function decodeUnicode(str) {
        return str.replace(/\\u([\d\w]{4})/gi, (match, grp) => {
            return String.fromCharCode(parseInt(grp, 16));
        });
    }
    function encodeUnicode(str) {
        const regex = /[^\x00-\x7F]/g; // Encuentra caracteres que no son ASCII
        return str.replace(regex, (char) => {
            return '\\u' + ('0000' + char.charCodeAt(0).toString(16)).slice(-4);
        });
    }
    async function getColonies() {
        const apiUrl = host + "/api/v1/maps/inegi/colonies?api_key="+ Api_key;
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error('Error al cargar los polígonos');
            }
            const data = await response.json();

            const coloniasListDiv = document.getElementById('coloniasList');
            coloniasListDiv.innerHTML = ''; // Limpiar contenido actual

            if (data.colonias && Array.isArray(data.colonias)) {
                data.colonias.forEach((colonia, index) => {
                    // Decodificar secuencias de escape unicode si es necesario
                    colonia = decodeUnicode(colonia);
                    // Solo agregamos el checkbox si el nombre de la colonia no está vacío
                    if (colonia.trim() !== '') {
                        const checkboxItem = document.createElement('div');
                        checkboxItem.classList.add('checkbox-item');

                        const coloniaValue = encodeURIComponent(colonia); // Codificar el valor
                        const coloniaLabel = document.createTextNode(colonia); // Crear nodo de texto para el label

                        checkboxItem.innerHTML = `
                            <input type="checkbox" id="colonia_${index}" name="colonias[]" value="${coloniaValue}">
                        `;
                        checkboxItem.appendChild(coloniaLabel); // Agregar el nodo de texto al div

                        coloniasListDiv.appendChild(checkboxItem);
                    }
                });
            } else {
                console.error('No se encontró el arreglo de colonias en la respuesta del servidor.');
            }

        } catch (error) {
            console.error('Error al obtener los polígonos:', error);
        }


    }
    async function getAcitiviy() {
        const apiUrl = host + "/api/v1/maps/inegi/economy_activity?api_key="+ Api_key;
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error('Error al cargar las actividades');
            }
            const data = await response.json();

            const actividadListDiv = document.getElementById('actividadList');
            actividadListDiv.innerHTML = ''; // Limpiar contenido actual
            if (data.Clase_actividad && Array.isArray(data.Clase_actividad)) {
                data.Clase_actividad.forEach((clase_actividad, index) => {
                    // Decodificar secuencias de escape unicode si es necesario
                    clase_actividad = decodeUnicode(clase_actividad);
                    // Solo agregamos el checkbox si el nombre de la clase_actividad no está vacío
                    if (clase_actividad.trim() !== '') {
                        const checkboxItem = document.createElement('div');
                        checkboxItem.classList.add('checkbox-item');

                        const clase_actividadValue = encodeURIComponent(clase_actividad); // Codificar el valor
                        const clase_actividadLabel = document.createTextNode(clase_actividad); // Crear nodo de texto para el label

                        checkboxItem.innerHTML = `
                            <input type="checkbox" id="clase_actividad_${index}" name="clase_actividad[]" value="${clase_actividadValue}">
                        `;
                        checkboxItem.appendChild(clase_actividadLabel); // Agregar el nodo de texto al div

                        actividadListDiv.appendChild(checkboxItem);
                    }
                });
            } else {
                console.error('No se encontró el arreglo de colonias en la respuesta del servidor.');
            }

        } catch (error) {
            console.error('Error al obtener los polígonos:', error);
        }


    }
    function getDataList(id_list) {
        const checkboxes = document.querySelectorAll(`#${id_list} input[type="checkbox"]:checked`);
        const valoresSeleccionados = [];

        checkboxes.forEach(checkbox => {
            // Decodificar el valor para evitar que los espacios se conviertan en %20
            const valorDecodificado = decodeURIComponent(checkbox.value);
            valoresSeleccionados.push(valorDecodificado);
        });

        return valoresSeleccionados;
    }
    function BIM_getelement(id){
        let valoresSeleccionados = [];
        const selectElement = document.getElementById(id);
        const valorSeleccionado = encodeUnicode(selectElement.value);
        if(valorSeleccionado != 0){
            valoresSeleccionados.push(valorSeleccionado);
            return valoresSeleccionados
        }
        return [];
    }
    function seleccionarTodas(id_checkbox) {
        const checkboxes = document.querySelectorAll(`#${id_checkbox} input[type="checkbox"]`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
    }
    function deseleccionarTodas(id_checkbox) {
        const checkboxes = document.querySelectorAll(`#${id_checkbox} input[type="checkbox"]`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
    document.addEventListener('DOMContentLoaded', function() {

        // Tu código aquí se ejecutará cuando el DOM esté completamente cargado
        BIM_mainMap(maparea);
        BIM_clearMarkers();
        BIM_getStratum();
        BIM_getCompanies();
        getColonies();
        getAcitiviy();
        
    });
</script>
