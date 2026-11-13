/**
 * ====================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : cart-sync.js
 * Description  : จัดการซิงค์ข้อมูล LocalStorage ของตะกร้าสินค้า และอัปเดต Badge บน Header ทุกหน้าอัตโนมัติ
 * ====================================================================
 */

document.addEventListener("DOMContentLoaded", function () {
    updateGlobalCartBadge();
});

// ฟังก์ชันกลางสำหรับอ่าน LocalStorage ทั้งหมด และอัปเดตตัวเลขและ Tooltip ที่ไอคอนตะกร้าบน Header
function updateGlobalCartBadge() {
    let totalCourses = 0;
    let totalAttendees = 0;

    // วนลูปกวาดหาข้อมูล LocalStorage ที่ขึ้นต้นด้วย sot_cart_attendees_ ของทุกหลักสูตร
    for (let i = 0; i < localStorage.length; i++) {
        let key = localStorage.key(i);
        if (key && key.startsWith('sot_cart_attendees_')) {
            try {
                let attendees = JSON.parse(localStorage.getItem(key));
                if (Array.isArray(attendees)) {
                    totalCourses++;
                    totalAttendees += attendees.length;
                }
            } catch (e) {
                // ข้ามหากแปลง JSON ไม่สำเร็จ
            }
        }
    }

    let badgeText = totalCourses + '/' + totalAttendees;
    let tooltipText = `จำนวน ${totalCourses} หลักสูตร\nผู้อบรม ${totalAttendees} ท่าน`;

    // อัปเดตตัวเลข Badge และ Tooltip (title) ที่ไอคอนตะกร้าบน Header ทุกหน้าเว็บ
    let cartBtn = document.getElementById('nav-cart-btn');
    if (cartBtn) {
        cartBtn.setAttribute('title', tooltipText);
    }

    document.querySelectorAll('#nav-cart-count').forEach(el => {
        el.style.display = totalCourses > 0 ? 'flex' : 'none';
        el.innerText = badgeText;
    });
}