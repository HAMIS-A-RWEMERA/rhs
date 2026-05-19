/* =========================================
   MOBILE MENU
========================================= */

const menuBtn = document.getElementById("menu-btn");
const navLinks = document.getElementById("nav-links");

if (menuBtn) {

    menuBtn.addEventListener("click", () => {

        navLinks.classList.toggle("active");

    });

}

/* =========================================
   ACADEMIC SEARCH FILTER
========================================= */

const searchInput = document.getElementById("academic-search");

if (searchInput) {

    searchInput.addEventListener("input", () => {

        const query = searchInput.value.toLowerCase();

        const cards = document.querySelectorAll(".academic-card");

        cards.forEach(card => {

            const text = card.innerText.toLowerCase();

            if (text.includes(query)) {

                card.style.display = "block";

            } else {

                card.style.display = "none";

            }

        });

    });

}

/* =========================================
   STUDENT PORTAL SYSTEM
========================================= */

const portalForm = document.getElementById("portal-form");

if (portalForm) {

    portalForm.addEventListener("submit", async (e) => {

        e.preventDefault();

        const studentId = document
            .getElementById("student-id")
            .value
            .trim();

        const pin = document
            .getElementById("student-pin")
            .value
            .trim();

        const resultBox = document.getElementById("result-box");

        resultBox.innerHTML = "Loading student data...";

        try {

            const response = await fetch("api/get_student.php", {

                method: "POST",

                headers: {
                    "Content-Type":
                    "application/x-www-form-urlencoded"
                },

                body: new URLSearchParams({
                    student_id: studentId,
                    pin: pin
                })

            });

            const data = await response.json();

            if (data.success) {

                resultBox.innerHTML = `
                    <div class="student-result">
                        <h3>${data.student.name}</h3>

                        <p><strong>Class:</strong>
                        ${data.student.class}</p>

                        <p><strong>Division:</strong>
                        ${data.student.division}</p>

                        <p><strong>Score:</strong>
                        ${data.student.score}</p>

                        <p><strong>Conduct:</strong>
                        ${data.student.conduct}</p>

                        <p><strong>Balance:</strong>
                        ${data.student.balance} RWF</p>
                    </div>
                `;

            } else {

                resultBox.innerHTML = `
                    <div class="error-box">
                        ${data.message}
                    </div>
                `;

            }

        } catch (error) {

            resultBox.innerHTML = `
                <div class="error-box">
                    Server connection failed.
                </div>
            `;

            console.error(error);

        }

    });

}