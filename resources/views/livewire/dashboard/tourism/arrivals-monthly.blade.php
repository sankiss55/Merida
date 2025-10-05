<style>
    @media (min-width: 768px) {
        .w-150-ps{
            width: 100%;
        }
    }
    @media (max-width: 767px) {
        .w-150-ps{
            width: 200%;
        }
        .div-overflow{
            overflow:auto;
        }
        .w-50-ps{
            width: 50%;
        }
    }
    @media (min-width: 768px) and (max-width: 1023px) {
    
    }
    .div-overflow{
            width:100%;
    }
</style>
@section('title',' Trafico De Pasajeros Mensual ')
<section class="h-full overflow-y-auto p-2">
    <div class="w-full h-full flex flex-wrap md:flex-nowrap space-y-2 md:space-y-0 md:space-x-2">
        <div class="div-overflow w-full ">
            <div class="w-full md:w-2/3 h-[50vh] md:h-full p-4 bg-white rounded-lg shadow-lg flex items-center w-150-ps">
                <canvas  id="chartMonthly"  class="w-full h-full" ></canvas>
            </div>
        </div>
        <div class="w-full md:w-1/3">
            <div class="flex flex-wrap">
                <div class="p-6 md:text-xl xl:text-2xl col-start-1 col-span-12 text-2xl text-center bg-white rounded-lg shadow-lg">
                    <ul>
                        <li>Trafico De Pasajeros Mensuales al Aeropuerto Internacional de la ciudad de Mérida</li>
                    </ul>
                    <div class="w-full">
            <label for="fromMonth" class="block text-sm font-medium text-gray-700  mb-1">Del mes</label>
            <select id="fromMonth" class="border rounded-lg p-2 shadow-sm focus:ring focus:ring-blue-300 w-full">
                 <option value="">Seleccionar</option>
            </select>
        </div>

        <!-- Select Al Mes -->
        <div  class="w-full">
            <label for="toMonth" class="block text-sm font-medium text-gray-700 mb-1">Al mes</label>
            <select id="toMonth" class="border rounded-lg p-2 shadow-sm focus:ring focus:ring-blue-300 w-full">
                 <option value="">Seleccionar</option>
            </select>
        </div>
                </div>
                
                <div class="my-5 w-full flex flex-wrap items-center mx-auto ">
                    <button onclick="exportChart()"
                            class="mx-auto text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow hover:bg-blue-500 py-2 px-4">
                        <i class="fas fa-file-download"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let collection=[];
        let collection_copy=[];
        let  chartMonthlyLine;
        function exportChart(){
            exportCanva(document.getElementById('chartMonthly'));
        }

        async function getMonthly(){
            today.setFullYear(today.getFullYear() - 3)
            let Y = today.getFullYear();
            let body = {year: Y};
            let URL = host + "/api/v1/tourism/arrivals/monthly?api_key=" + Api_key;
            const response = await fetch(URL, {
                method: 'GET'
            });
            if (response.ok) {
                const result = await response.json();
                console.log(result);
                 
                if (result.body !== null)
                    collection=result.body;
                    collection_copy=result.body;
                drawChartMonthly();
                  const fromMonth = document.getElementById("fromMonth");
            const toMonth = document.getElementById("toMonth");

            fromMonth.innerHTML = '<option value="">Seleccionar</option>';
            toMonth.innerHTML = '<option value="">Seleccionar</option>';

for (let data of collection) {
    const option = document.createElement("option");
    option.textContent = `${data.int_year}-${data.str_month}`; 
    option.value = `${data.int_year}-${data.int_month}`;       
    fromMonth.append(option);
}
 fromMonth.addEventListener("change", () => {
                const selectedValue = fromMonth.value; 
                if(!selectedValue) {
                    toMonth.innerHTML = '<option value="">Seleccionar</option>';      
                    collection=[...collection_copy];          
                      drawChartMonthly();
                    return;
                }

                const [selectedYear, selectedMonth] = selectedValue.split("-").map(Number);
                const filtered = collection_copy.filter(item => {
                    return item.int_year > selectedYear ||
                           (item.int_year === selectedYear && item.int_month >= selectedMonth);
                });

                toMonth.innerHTML = '<option value="">Seleccionar</option>';
                for (let data of filtered) {
                    const option = document.createElement("option");
                    option.textContent = `${data.int_year}-${data.str_month}`;
                    option.value = `${data.int_year}-${data.int_month}`;
                    toMonth.append(option);
                }
                
            });
            toMonth.addEventListener("change", () => {
    const fromValue = fromMonth.value;
    const toValue = toMonth.value;
    if (!fromValue || !toValue) {
        collection = [...collection_copy];
        return;
    }

    const [fromYear, fromMonthNum] = fromValue.split("-").map(Number);
    const [toYear, toMonthNum] = toValue.split("-").map(Number);

    collection = collection_copy.filter(item => {
        const itemYear = item.int_year;
        const itemMonth = item.int_month;
        const afterStart = itemYear > fromYear || (itemYear === fromYear && itemMonth >= fromMonthNum);
        const beforeEnd = itemYear < toYear || (itemYear === toYear && itemMonth <= toMonthNum);
        return afterStart && beforeEnd;
    });

    console.log("Collection filtrada:", collection);
drawChartMonthly();
});

            }
        }

        function drawChartMonthly(){
            const ctxMonthly=document.getElementById('chartMonthly').getContext('2d');
             if (chartMonthlyLine) {
        chartMonthlyLine.destroy();
    }
            let rgb;
            let labels=[];
            let datasets=[];
            let totals=[], internationals=[], domestics=[];
            collection.map(item => {
                labels.push(item.str_month+" "+item.int_year);
                totals.push(item.total);
                domestics.push(item.domestic)
                internationals.push(item.international)
            })
            rgb=rndBgColor([1])
            datasets.push({backgroundColor:rgb,
                            borderColor: rgb,
                            label:'Domestic',
                            data: domestics,
                        });
            rgb=rndBgColor([1])
            datasets.push({backgroundColor:rgb,
                            borderColor: rgb,
                            label:'International',
                            data: internationals,
                            });
            rgb=rndBgColor([1])
            datasets.push({backgroundColor:rgb,
                            borderColor: rgb,
                            label:'Total',
                            data: totals,
                        });


            ConfigLine.options.pointRadius=5;
            ConfigLine.options.tension=0;
            ConfigLine.options.plugins.legend.display=true;
            ConfigLine.options.plugins.legend.position='bottom';
            ConfigLine.options.plugins.title.text = 'Trafico De Pasajeros MENSUAL ';
            ConfigLine.options.plugins.datalabels.display=true;
            ConfigLine.data.labels = labels;
            ConfigLine.data.datasets=datasets;
            chartMonthlyLine= new Chart(ctxMonthly, ConfigLine);
        }

        function init(){
            getMonthly();
        }
        window.onload=function(){
            init();
        };

    </script>
</section>

