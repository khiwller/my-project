<?php 
// จะแสดงแชทก็ต่อเมื่อลูกค้าล็อกอินแล้วเท่านั้น
if(isset($_SESSION['user_id'])): 
?>
<button id="chatToggleBtn" class="fixed bottom-6 right-6 bg-[#121212] text-[#D4AF37] p-4 rounded-full shadow-2xl hover:bg-[#D4AF37] hover:text-[#121212] transition-all duration-300 z-50">
    <i class="fas fa-comments text-2xl"></i>
    <span id="unreadBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-6 h-6 flex items-center justify-center rounded-full border-2 border-white hidden transform transition-transform scale-0">0</span>
</button>

<div id="chatBox" class="fixed bottom-24 right-6 w-80 bg-white rounded-lg shadow-2xl overflow-hidden z-50 hidden border border-gray-200 transform transition-all">
    <div class="bg-[#121212] text-[#D4AF37] p-4 flex justify-between items-center border-b-2 border-[#D4AF37]">
        <h3 class="font-bold font-prompt"><i class="fas fa-headset mr-2"></i> ติดต่อเจ้าหน้าที่</h3>
        <button id="chatCloseBtn" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
    </div>
    
    <div id="chatBody" class="p-4 h-72 overflow-y-auto bg-gray-50 flex flex-col font-prompt">
        </div>
    
    <div class="p-3 bg-white border-t border-gray-200">
        <form id="chatForm" class="flex items-center">
            <input type="text" id="chatInput" class="flex-1 bg-gray-100 border-none rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#D4AF37]" placeholder="พิมพ์ข้อความ..." autocomplete="off">
            <button type="submit" class="ml-2 bg-[#D4AF37] text-white rounded-full w-9 h-9 flex items-center justify-center hover:bg-[#b8860b] transition">
                <i class="fas fa-paper-plane text-xs relative right-[1px]"></i>
            </button>
        </form>
    </div>
</div>

<script>
    const chatToggleBtn = document.getElementById('chatToggleBtn');
    const chatCloseBtn = document.getElementById('chatCloseBtn');
    const chatBox = document.getElementById('chatBox');
    const chatBody = document.getElementById('chatBody');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const unreadBadge = document.getElementById('unreadBadge');
    let chatInterval;
    let badgeInterval;

    // 🌟 ระบบดึงจำนวนข้อความแจ้งเตือนที่ยังไม่อ่าน
    function checkUnread() {
        if (!chatBox.classList.contains('hidden')) {
            hideBadge();
            return;
        }

        fetch('chat_count_unread.php')
            .then(res => res.text())
            .then(count => {
                // 🛑 แอบดูหลังบ้านว่า PHP ส่งค่าอะไรมาให้
                console.log("ค่าที่ดึงได้จาก Database:", count); 

                let num = parseInt(count.trim()); // ตัดช่องว่างทิ้งก่อนแปลงเป็นตัวเลข
                
                if (!isNaN(num) && num > 0) {
                    unreadBadge.textContent = num > 99 ? '99+' : num;
                    // เอา hidden ออก เพื่อให้ Badge แสดงผล
                    unreadBadge.classList.remove('hidden', 'scale-0');
                    unreadBadge.classList.add('flex', 'scale-100');
                } else {
                    hideBadge();
                }
            })
            .catch(err => console.error('Error fetching unread count:', err));
    }

    function hideBadge() {
        unreadBadge.classList.remove('scale-100');
        unreadBadge.classList.add('scale-0');
        setTimeout(() => unreadBadge.classList.add('hidden'), 200);
    }

    // เริ่มทำงานระบบแจ้งเตือนเมื่อโหลดหน้าเว็บ
    checkUnread();
    badgeInterval = setInterval(checkUnread, 3000);

    // เปิด/ปิด หน้าต่างแชท
    function toggleChat() {
        chatBox.classList.toggle('hidden');
        if (!chatBox.classList.contains('hidden')) {
            hideBadge(); // ซ่อนแจ้งเตือนเมื่อเปิดอ่าน
            loadMessages(); // โหลดข้อความทันทีที่เปิด
            chatInterval = setInterval(loadMessages, 2000); // ดึงข้อความใหม่ทุกๆ 2 วินาที
            scrollToBottom();
        } else {
            clearInterval(chatInterval); // หยุดดึงข้อมูลเมื่อปิดแชท
            checkUnread(); // กลับมาเช็คแจ้งเตือนต่อเมื่อปิดหน้าต่าง
        }
    }

    chatToggleBtn.addEventListener('click', toggleChat);
    chatCloseBtn.addEventListener('click', toggleChat);

    // ฟังก์ชันเลื่อนแชทลงล่างสุด
    function scrollToBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    // AJAX: ดึงข้อความ
    function loadMessages() {
        fetch('chat_fetch.php')
            .then(response => response.text())
            .then(data => {
                // อัปเดตข้อความเฉพาะตอนที่มีการเปลี่ยนแปลง เพื่อไม่ให้หน้าจอกระตุก
                if(chatBody.innerHTML !== data) {
                    chatBody.innerHTML = data;
                    scrollToBottom();
                }
            });
    }

    // AJAX: ส่งข้อความ
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (msg === '') return;

        // ล้างช่องกรอกทันทีให้ดูลื่นไหล
        chatInput.value = '';

        // ส่งข้อมูลไปเซฟ
        const formData = new FormData();
        formData.append('message', msg);

        fetch('chat_send.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            loadMessages(); // โหลดข้อความใหม่มาแสดงทันที
        });
    });
</script>
<?php endif; ?>