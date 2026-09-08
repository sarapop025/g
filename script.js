
// ========================================
// SCHOOL GRADE SYSTEM
// JavaScript
// ========================================

document.addEventListener("DOMContentLoaded", function () {

    console.log("School Grade System Ready");


    // ========================================
    // Confirm Delete
    // ========================================

    const deleteLinks =
        document.querySelectorAll(".delete-confirm");

    deleteLinks.forEach(function (link) {

        link.addEventListener("click", function (event) {

            const message =
                link.dataset.message ||
                "ต้องการลบข้อมูลนี้หรือไม่?";

            if (!confirm(message)) {

                event.preventDefault();

            }

        });

    });


    // ========================================
    // Confirm Form
    // ========================================

    const confirmForms =
        document.querySelectorAll(".confirm-form");

    confirmForms.forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const message =
                form.dataset.message ||
                "ยืนยันการทำรายการหรือไม่?";

            if (!confirm(message)) {

                event.preventDefault();

            }

        });

    });


    // ========================================
    // จำกัดคะแนน 0 - 100
    // ========================================

    const scoreInputs =
        document.querySelectorAll(
            'input[name="score"]'
        );

    scoreInputs.forEach(function (input) {

        input.addEventListener("input", function () {

            let value =
                parseFloat(input.value);

            if (value < 0) {

                input.value = 0;

            }

            if (value > 100) {

                input.value = 100;

            }

        });

    });


    // ========================================
    // แสดงเกรดทันทีจากคะแนน
    // ========================================

    const gradeScore =
        document.getElementById("gradeScore");

    const gradeResult =
        document.getElementById("gradeResult");

    if (gradeScore && gradeResult) {

        function calculateGrade(score) {

            if (score >= 80) return "4.0";
            if (score >= 75) return "3.5";
            if (score >= 70) return "3.0";
            if (score >= 65) return "2.5";
            if (score >= 60) return "2.0";
            if (score >= 55) return "1.5";
            if (score >= 50) return "1.0";

            return "0.0";
        }


        function showGrade() {

            const score =
                parseFloat(
                    gradeScore.value
                );


            if (
                isNaN(score) ||
                score < 0 ||
                score > 100
            ) {

                gradeResult.textContent =
                    "-";

                return;
            }


            gradeResult.textContent =
                calculateGrade(score);

        }


        gradeScore.addEventListener(
            "input",
            showGrade
        );


        showGrade();

    }


    // ========================================
    // แสดง / ซ่อน Password
    // ========================================

    const passwordToggle =
        document.getElementById(
            "togglePassword"
        );

    const passwordInput =
        document.getElementById(
            "password"
        );


    if (
        passwordToggle &&
        passwordInput
    ) {

        passwordToggle.addEventListener(
            "click",
            function () {

                if (
                    passwordInput.type ===
                    "password"
                ) {

                    passwordInput.type =
                        "text";

                    passwordToggle.textContent =
                        "ซ่อนรหัสผ่าน";

                } else {

                    passwordInput.type =
                        "password";

                    passwordToggle.textContent =
                        "แสดงรหัสผ่าน";

                }

            }
        );

    }


    // ========================================
    // Auto Submit Select
    // ========================================

    const autoSubmit =
        document.querySelectorAll(
            ".auto-submit"
        );

    autoSubmit.forEach(function (select) {

        select.addEventListener(
            "change",
            function () {

                if (select.form) {

                    select.form.submit();

                }

            }
        );

    });


    // ========================================
    // Search Table
    // ========================================

    const tableSearch =
        document.getElementById(
            "tableSearch"
        );

    const searchTable =
        document.getElementById(
            "searchTable"
        );


    if (
        tableSearch &&
        searchTable
    ) {

        tableSearch.addEventListener(
            "input",
            function () {

                const keyword =
                    tableSearch.value
                        .toLowerCase()
                        .trim();


                const rows =
                    searchTable.querySelectorAll(
                        "tbody tr"
                    );


                rows.forEach(function (row) {

                    const text =
                        row.textContent
                            .toLowerCase();


                    if (
                        text.includes(keyword)
                    ) {

                        row.style.display =
                            "";

                    } else {

                        row.style.display =
                            "none";

                    }

                });

            }
        );

    }


    // ========================================
    // Select All Checkbox
    // ========================================

    const selectAll =
        document.getElementById(
            "selectAll"
        );

    const checkboxes =
        document.querySelectorAll(
            ".row-checkbox"
        );


    if (selectAll) {

        selectAll.addEventListener(
            "change",
            function () {

                checkboxes.forEach(
                    function (checkbox) {

                        checkbox.checked =
                            selectAll.checked;

                    }
                );

            }
        );

    }


    // ========================================
    // Hide Alert Automatically
    // ========================================

    const alerts =
        document.querySelectorAll(
            ".auto-hide"
        );


    alerts.forEach(function (alert) {

        setTimeout(function () {

            alert.style.opacity = "0";

            setTimeout(function () {

                alert.style.display =
                    "none";

            }, 500);

        }, 3000);

    });


    // ========================================
    // ป้องกัน Double Submit
    // ========================================

    const forms =
        document.querySelectorAll(
            "form"
        );


    forms.forEach(function (form) {

        form.addEventListener(
            "submit",
            function () {

                const submitButtons =
                    form.querySelectorAll(
                        'button[type="submit"]'
                    );


                submitButtons.forEach(
                    function (button) {

                        if (
                            !button.disabled
                        ) {

                            button.disabled =
                                true;

                            button.dataset.originalText =
                                button.textContent;

                            button.textContent =
                                "กำลังบันทึก...";

                        }

                    }
                );

            }
        );

    });

});