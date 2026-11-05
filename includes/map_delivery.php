<script src="https://api.longdo.com/map/?key=70a15443673186fdac9c1c56bca19dcd"></script>

<div class="bg-white shadow-lg border border-gray-100 rounded-xl overflow-hidden mb-6">
    <div class="bg-[#121212] py-4 px-6 border-b border-yellow-900/20">
        <h5 class="text-[#D4AF37] font-bold flex items-center gap-2 mb-0">
            <i class="bi bi-geo-alt-fill"></i> ค้นหาที่อยู่และปักหมุดจัดส่ง
        </h5>
    </div>

    <div class="p-6 space-y-6">
        <div>
            <label class="block font-bold text-gray-700 text-xs uppercase tracking-wider mb-2">
                เบอร์โทรศัพท์ติดต่อผู้รับ <span class="text-red-500">*</span>
            </label>
            <input type="tel" 
                name="shipping_phone" 
                class="w-full bg-gray-50 border-0 rounded-lg p-3 text-base focus:ring-2 focus:ring-[#D4AF37] outline-none transition-all" 
                value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" 
                placeholder="08xxxxxxxx" 
                maxlength="10"
                oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                required>
            <p class="mt-1 text-[10px] text-gray-400 font-medium italic">* กรุณากรอกเฉพาะตัวเลข 10 หลัก</p>
        </div>

        <div class="relative">
            <label class="block font-bold text-gray-700 text-xs uppercase tracking-wider mb-2">
                ค้นหาสถานที่จัดส่ง / ชื่อวัด <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden group focus-within:ring-2 focus-within:ring-[#D4AF37] transition-all">
                <span class="px-4 text-gray-400 border-r border-gray-50"><i class="bi bi-search"></i></span>
                <input type="text" id="searchBox" 
                       class="w-full p-3 text-base border-0 outline-none" 
                       placeholder="พิมพ์ชื่อสถานที่, ชื่อวัด หรือซอย...">
            </div>
            <ul id="suggestList" 
                class="absolute left-0 right-0 bg-white shadow-2xl z-[1050] rounded-b-lg max-h-[250px] overflow-y-auto border border-gray-100 divide-y divide-gray-50 mt-1" 
                style="display: none;"></ul>
        </div>

        <div class="relative">
            <div id="map" class="shadow-sm border border-gray-100 rounded-xl overflow-hidden h-[400px] w-full"></div>
            <p class="text-center text-gray-400 text-xs mt-3 italic">
                <i class="bi bi-info-circle text-[#D4AF37]"></i> ท่านสามารถคลิกหรือเลื่อนหมุดสีแดงบนแผนที่ เพื่อระบุพิกัดที่แน่นอนได้
            </p>
        </div>

        <div id="status" class="hidden bg-blue-50 text-blue-600 text-center py-2.5 rounded-full text-sm animate-pulse border border-blue-100">
            <div class="inline-block w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mr-2 align-middle"></div> 
            กำลังคำนวณระยะทางและค่าจัดส่ง...
        </div>

        <div id="summary" class="hidden mt-4 p-5 rounded-xl border border-gray-50 bg-gray-50/50 shadow-inner">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="text-[10px] text-gray-400 uppercase font-black tracking-widest italic">Destination:</div>
                    <div id="locationText" class="text-gray-900 font-bold text-base"></div>
                    <div class="text-sm text-gray-500 font-light flex items-center gap-2">
                        <i class="bi bi-truck text-[#D4AF37]"></i> ระยะทาง: <span id="distText" class="text-gray-900 font-bold"></span>
                    </div>
                </div>
                <div class="md:text-right md:border-l border-gray-200 md:pl-6 pt-3 md:pt-0">
                    <div class="text-[10px] text-gray-400 uppercase font-black tracking-widest ">ค่าจัดส่ง:</div>
                    <div id="priceText" class="text-2xl font-black text-blue-600 "></div>
                </div>
            </div>
        </div>

        <input type="hidden" name="delivery_fee" id="hidden_delivery_fee" value="0">
        <input type="hidden" name="delivery_distance" id="hidden_distance" value="0">
        <input type="hidden" name="delivery_lat" id="hidden_lat" value="">
        <input type="hidden" name="delivery_lng" id="hidden_lng" value="">
        <input type="hidden" name="shipping_address_detail" id="hidden_address" value="">

        <div class="border-l-4 border-yellow-500 bg-yellow-50/50 p-4 text-gray-500 text-xs rounded-r-lg">
            <i class="bi bi-info-circle-fill text-yellow-600 mr-2"></i> 
            <strong class="text-gray-700 text-[16px]">เงื่อนไขค่าจัดส่ง:</strong> <strong class="text-gray-700 text-[16px]">ฟรีในเขต จ.อยุธยา | นอกพื้นที่คิดตามระยะทางจริง กม. ละ 15 บาท</strong>
        </div>

    </div>
</div>

<script>
    const LONGDO_API_KEY = '70a15443673186fdac9c1c56bca19dcd'; 
    const START_COORDS = { lat: 14.3588, lon: 100.5613 }; // พิกัดร้าน
    const PRICE_PER_KM = 15; 
    let map, deliveryMarker, routeDebounceTimer, suggestTimer;

    function initMap() {
        map = new longdo.Map({
            placeholder: document.getElementById('map'),
            language: 'th'
        });
        
        map.location({ lon: START_COORDS.lon, lat: START_COORDS.lat }, true);
        map.zoom(12, true);

        // หมุดร้าน (สีน้ำเงิน/เขียว)
        map.Overlays.add(new longdo.Marker({ lon: START_COORDS.lon, lat: START_COORDS.lat }, {
            title: 'ร้านสมหมายหีบทอง (อยุธยา)',
            detail: 'จุดเริ่มต้นการจัดส่ง',
        }));
        
        // หมุดลูกค้า
        deliveryMarker = new longdo.Marker({ lon: START_COORDS.lon, lat: START_COORDS.lat }, {
            draggable: true,
            title: 'จุดจัดส่งของท่าน',
        });
        map.Overlays.add(deliveryMarker);

        map.Event.bind('overlayDrop', function(overlay) {
            if (overlay === deliveryMarker) {
                let pos = deliveryMarker.location();
                handleLocationUpdate(pos.lat, pos.lon);
            }
        });
        
        map.Event.bind('click', function() {
            let mouseLocation = map.location(longdo.LocationMode.Pointer);
            map.Overlays.remove(deliveryMarker);
            deliveryMarker = new longdo.Marker(mouseLocation, { draggable: true, title: 'จุดจัดส่ง' });
            map.Overlays.add(deliveryMarker);
            handleLocationUpdate(mouseLocation.lat, mouseLocation.lon);
        });

        setupAutocomplete();
    }

    function setupAutocomplete() {
        const searchBox = document.getElementById('searchBox');
        const suggestList = document.getElementById('suggestList');

        searchBox.addEventListener('input', function() {
            clearTimeout(suggestTimer);
            const keyword = this.value.trim();
            if (keyword.length < 2) { suggestList.style.display = 'none'; return; }

            suggestTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`https://search.longdo.com/mapsearch/json/suggest?keyword=${encodeURIComponent(keyword)}&key=${LONGDO_API_KEY}`);
                    const data = await res.json();
                    const suggestions = data.data || [];
                    
                    suggestList.innerHTML = '';
                    if (suggestions.length > 0) {
                        suggestions.forEach(item => {
                            const li = document.createElement('li');
                            li.className = 'p-3 hover:bg-gray-50 cursor-pointer transition-colors flex items-start gap-3';
                            li.innerHTML = `
                                <i class="bi bi-geo-alt text-red-500 mt-1"></i> 
                                <div>
                                    <div class="text-sm font-bold text-gray-900">${item.w}</div>
                                    <div class="text-xs text-gray-400">${item.d || ''}</div>
                                </div>`;
                            li.onclick = () => {
                                searchBox.value = item.w;
                                suggestList.style.display = 'none';
                                executeSearch(item.w); 
                            };
                            suggestList.appendChild(li);
                        });
                        suggestList.style.display = 'block';
                    }
                } catch(e) { console.error(e); }
            }, 400); 
        });
    }

    async function executeSearch(keyword) {
        document.getElementById('status').style.display = 'block';
        try {
            const response = await fetch(`https://search.longdo.com/mapsearch/json/search?keyword=${encodeURIComponent(keyword)}&limit=1&key=${LONGDO_API_KEY}`);
            const data = await response.json();
            const res = data.data || data; 
            
            if (res && res.length > 0) {
                const lat = res[0].lat, lon = res[0].lon;
                map.location({ lon: lon, lat: lat }, true);
                map.zoom(16, true);
                map.Overlays.remove(deliveryMarker);
                deliveryMarker = new longdo.Marker({ lon: lon, lat: lat }, { draggable: true, title: 'จุดจัดส่ง' });
                map.Overlays.add(deliveryMarker);
                handleLocationUpdate(lat, lon);
            }
        } catch (error) { console.error(error); }
    }

    function handleLocationUpdate(lat, lon) {
        clearTimeout(routeDebounceTimer);
        document.getElementById('status').style.display = 'block';
        routeDebounceTimer = setTimeout(() => { calculateShipping(lat, lon); }, 600);
    }

    async function calculateShipping(lat, lon) {
        const statusEl = document.getElementById('status');
        const summaryEl = document.getElementById('summary');

        try {
            const geoResp = await fetch(`https://api.longdo.com/map/services/address?lon=${lon}&lat=${lat}&key=${LONGDO_API_KEY}`);
            const geoData = await geoResp.json();
            const fullAddress = `${geoData.subdistrict || ''} ${geoData.district || ''} ${geoData.province || ''}`.trim();
            const isAyutthaya = (geoData.province || "").includes("อยุธยา");

            const routeResp = await fetch(`https://api.longdo.com/RouteService/json/route/guide?flon=${START_COORDS.lon}&flat=${START_COORDS.lat}&tlon=${lon}&tlat=${lat}&mode=t&key=${LONGDO_API_KEY}`);
            const routeData = await routeResp.json();
            
            if (!routeData?.data?.[0]) throw new Error("Route not found");

            const distanceKm = routeData.data[0].distance / 1000;
            const deliveryFee = isAyutthaya ? 0 : Math.round(distanceKm * PRICE_PER_KM);

            document.getElementById('hidden_delivery_fee').value = deliveryFee;
            document.getElementById('hidden_distance').value = distanceKm.toFixed(2);
            document.getElementById('hidden_lat').value = lat;
            document.getElementById('hidden_lng').value = lon;
            document.getElementById('hidden_address').value = fullAddress;

            window.deliveryFee = deliveryFee;
            window.deliveryLat = lat;

            statusEl.style.display = 'none';
            summaryEl.classList.remove('hidden');
            summaryEl.style.display = 'block';
            document.getElementById('locationText').innerText = fullAddress || "พิกัดที่เลือก";
            document.getElementById('distText').innerText = distanceKm.toFixed(2) + " กม.";
            document.getElementById('priceText').innerText = deliveryFee === 0 ? "ส่งฟรี" : "฿ " + deliveryFee.toLocaleString();
            
            if (typeof calculateGrandTotal === 'function') {
                calculateGrandTotal();
            }

        } catch (error) {
            statusEl.style.display = 'none';
            alert('ไม่สามารถคำนวณเส้นทางได้ กรุณาเลือกจุดที่ใกล้ถนนสาธารณะครับ');
        }
    }

    window.addEventListener('load', initMap);
</script>