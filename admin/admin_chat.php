<?php session_start(); 
require_once 'layout/header.php'; 
checkAdmin()?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบตอบแชทลูกค้า | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* 🛠️ ปิดการเลื่อนของหน้าจอหลัก บังคับให้เลื่อนแค่ในห้องแชท */
        body { 
            font-family: 'Prompt', sans-serif; 
            background-color: #f4f7f6; 
            overflow: hidden; 
        }

        .theme-gold { color: #D4AF37; }
        .bg-theme-dark { background-color: #121212; color: #D4AF37; }
        .btn-theme-dark { background-color: #121212; color: #D4AF37; border: 1px solid #D4AF37; }
        .btn-theme-dark:hover { background-color: #D4AF37; color: #121212; }

        /* 🌟 หักลบความสูงของ Navbar ด้านบน (ปรับตัวเลข 75px ได้ตามความสูงจริงของ Navbar คุณ) */
        .main-wrapper {
            height: calc(100vh - 75px);
        }

        /* แต่ง Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 10px; }

        /* Sidebar Item */
        .user-list-item { cursor: pointer; transition: 0.2s; border-left: 4px solid transparent; }
        .user-list-item:hover { background-color: #f8f9fa; border-left: 4px solid #D4AF37; }
        .user-list-item.active { background-color: #fff9e6; border-left: 4px solid #D4AF37; }

        /* ตกแต่งฟองสบู่แชท */
        .chat-bubble-admin {
            background-color: #121212; color: #D4AF37;
            border-radius: 15px 15px 0 15px;
            max-width: 75%; padding: 10px 15px; border: 1px solid #D4AF37;
        }
        .chat-bubble-user {
            background-color: white; color: #333;
            border-radius: 15px 15px 15px 0;
            max-width: 75%; padding: 10px 15px; border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>

    <div class="container-fluid main-wrapper p-0">
        <div class="row g-0 h-100">
            
            <div class="col-md-4 col-lg-3 d-flex flex-column border-end bg-white h-100 shadow-sm">
                <div class="p-3 bg-theme-dark flex-shrink-0" style="border-bottom: 4px solid #D4AF37;">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-inbox me-2"></i> ข้อความจากลูกค้า</h5>
                </div>
                <div id="userList" class="flex-grow-1 overflow-y-auto">
                    </div>
            </div>

            <div class="col-md-8 col-lg-9 d-flex flex-column bg-light h-100 position-relative">
                
                <div id="placeholder" class="position-absolute top-0 bottom-0 start-0 end-0 d-flex flex-column align-items-center justify-content-center bg-light" style="z-index: 10;">
                    <i class="fas fa-comments fa-4x text-secondary mb-3 opacity-50"></i>
                    <p class="text-secondary fw-bold">เลือกการสนทนาเพื่อเริ่มต้นตอบแชท</p>
                </div>

                <div class="p-3 bg-white border-bottom shadow-sm flex-shrink-0">
                    <h5 id="activeUserName" class="mb-0 fw-bold text-dark"><i class="fas fa-user-circle me-2 text-muted"></i>กำลังสนทนากับ: -</h5>
                </div>

                <div id="chatBody" class="flex-grow-1 p-4" style="overflow-y: auto; min-height: 0; background-color: #f8f9fa;">
                    </div>

                <div class="p-3 bg-white border-top flex-shrink-0 mt-auto">
                    <form id="adminChatForm" class="d-flex align-items-center bg-light p-1 rounded-pill border">
                        <input type="hidden" id="activeUserId" value="">
                        <input type="text" id="adminChatInput" class="form-control border-0 bg-transparent px-4 py-2" placeholder="พิมพ์ข้อความตอบกลับ..." disabled required autocomplete="off">
                        <button type="submit" id="sendBtn" class="btn btn-theme-dark rounded-circle d-flex align-items-center justify-content-center mx-1" style="width: 45px; height: 45px;" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let activeUser = null;
    let chatInterval;

    function loadUsers() {
        fetch('admin_get_users.php')
            .then(res => res.text())
            .then(data => { 
                const userList = document.getElementById('userList');
                const currentScroll = userList.scrollTop;
                userList.innerHTML = data; 
                userList.scrollTop = currentScroll;
            });
    }
    loadUsers();
    setInterval(loadUsers, 5000);

    function loadChat(userId, userName) {
        activeUser = userId;
        document.getElementById('activeUserId').value = userId;
        document.getElementById('activeUserName').innerHTML = `<i class="fas fa-user-circle me-2 theme-gold"></i>กำลังสนทนากับ: <span class="theme-gold">${userName}</span>`;
        
        document.getElementById('placeholder').classList.add('d-none');
        document.getElementById('adminChatInput').disabled = false;
        document.getElementById('sendBtn').disabled = false;
        document.getElementById('adminChatInput').focus();

        if(chatInterval) clearInterval(chatInterval);
        fetchMessages(); 
        chatInterval = setInterval(fetchMessages, 2000); 
    }

    function fetchMessages() {
        if(!activeUser) return;
        fetch(`admin_get_chat.php?user_id=${activeUser}`)
            .then(res => res.text())
            .then(data => {
                const chatBody = document.getElementById('chatBody');
                if(chatBody.innerHTML !== data) {
                    chatBody.innerHTML = data;
                    chatBody.scrollTop = chatBody.scrollHeight;
                }
            });
    }

    document.getElementById('adminChatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('adminChatInput');
        const userId = document.getElementById('activeUserId').value;
        const msg = input.value.trim();
        
        if (msg === '' || !userId) return;
        input.value = ''; 

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('message', msg);

        fetch('admin_send_chat.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            fetchMessages(); 
        });
    });
</script>
</body>
</html>