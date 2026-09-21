document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =====================================================
           CURRENT YEAR
        ===================================================== */

        const currentYear =
            document.getElementById(
                "currentYear"
            );


        if (currentYear) {

            currentYear.textContent =
                new Date().getFullYear();

        }



        /* =====================================================
           MOBILE NAVBAR
        ===================================================== */

        const navbar =
            document.getElementById(
                "studioNavbar"
            );


        const navLinks =
            document.querySelectorAll(
                "#studioNavbar .nav-link"
            );


        navLinks.forEach(
            function (link) {

                link.addEventListener(
                    "click",
                    function () {

                        if (
                            navbar &&
                            navbar.classList.contains(
                                "show"
                            ) &&
                            typeof bootstrap !== "undefined"
                        ) {

                            bootstrap.Collapse
                                .getOrCreateInstance(
                                    navbar
                                )
                                .hide();

                        }

                    }
                );

            }
        );



        /* =====================================================
           OTHER SCROLL ANIMATIONS
        ===================================================== */

        const revealElements =
            document.querySelectorAll(
                `
                .scroll-reveal,
                .scroll-reveal-left,
                .scroll-reveal-right
                `
            );


        revealElements.forEach(
            function (element) {

                element.classList.add(
                    "reveal-ready"
                );

            }
        );



        if (
            "IntersectionObserver" in window
        ) {

            const revealObserver =
                new IntersectionObserver(

                    function (
                        entries,
                        observer
                    ) {

                        entries.forEach(
                            function (entry) {

                                if (
                                    entry.isIntersecting
                                ) {

                                    entry.target
                                        .classList
                                        .add(
                                            "is-visible"
                                        );


                                    observer.unobserve(
                                        entry.target
                                    );

                                }

                            }
                        );

                    },

                    {

                        threshold:
                            0.12,

                        rootMargin:
                            "0px 0px -8% 0px"

                    }

                );


            revealElements.forEach(
                function (element) {

                    revealObserver.observe(
                        element
                    );

                }
            );

        }

        else {

            revealElements.forEach(
                function (element) {

                    element.classList.add(
                        "is-visible"
                    );

                }
            );

        }



        /* =====================================================
           FORM ELEMENTS
        ===================================================== */

        const bookingModal =
            document.getElementById(
                "bookingModal"
            );


        const bookingForm =
            document.getElementById(
                "bookingForm"
            );


        const treatmentSelect =
            document.getElementById(
                "treatmentSelect"
            );


        const phoneInput =
            document.getElementById(
                "clientPhone"
            );


        const dateInput =
            document.getElementById(
                "appointmentDate"
            );


        const bookingSuccess =
            document.getElementById(
                "bookingSuccess"
            );



        /* =====================================================
           MINIMUM DATE
        ===================================================== */

        if (dateInput) {

            const today =
                new Date();


            const year =
                today.getFullYear();


            const month =
                String(
                    today.getMonth() + 1
                ).padStart(
                    2,
                    "0"
                );


            const day =
                String(
                    today.getDate()
                ).padStart(
                    2,
                    "0"
                );


            dateInput.min =
                `${year}-${month}-${day}`;

        }



        /* =====================================================
           AUTOMATIC SERVICE SELECTION
        ===================================================== */

        if (bookingModal) {

            bookingModal.addEventListener(
                "show.bs.modal",
                function (event) {


                    const triggerButton =
                        event.relatedTarget;


                    if (
                        !triggerButton ||
                        !treatmentSelect
                    ) {

                        return;

                    }


                    const service =
                        triggerButton.getAttribute(
                            "data-service"
                        );


                    if (service) {

                        treatmentSelect.value =
                            service;

                    }

                }
            );

        }



        /* =====================================================
           PHONE VALIDATION
        ===================================================== */

        function cleanPhone(
            value
        ) {

            return value.replace(
                /[^0-9+\s()\-]/g,
                ""
            );

        }



        function validPhone(
            value
        ) {

            const cleaned =
                value.replace(
                    /[\s()\-]/g,
                    ""
                );


            const ukPhonePattern =
                /^(?:0\d{10}|\+44\d{10})$/;


            return ukPhonePattern.test(
                cleaned
            );

        }



        function validatePhone() {

            if (!phoneInput) {

                return true;

            }


            const value =
                phoneInput.value.trim();


            if (
                !value ||
                !validPhone(value)
            ) {

                phoneInput.setCustomValidity(
                    "Please enter a valid UK phone number."
                );


                return false;

            }


            phoneInput.setCustomValidity(
                ""
            );


            return true;

        }



        if (phoneInput) {

            phoneInput.addEventListener(
                "input",
                function () {

                    phoneInput.value =
                        cleanPhone(
                            phoneInput.value
                        );


                    validatePhone();

                }
            );


            phoneInput.addEventListener(
                "blur",
                validatePhone
            );

        }



        /* =====================================================
           FORM SUBMIT
        ===================================================== */

if (bookingForm) {
    bookingForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        validatePhone();

        if (!bookingForm.checkValidity()) {
            bookingForm.classList.add("was-validated");

            const firstInvalid = bookingForm.querySelector(":invalid");

            if (firstInvalid) {
                firstInvalid.focus();
            }

            return;
        }

        bookingForm.classList.add("was-validated");

        const submitButton = bookingForm.querySelector('button[type="submit"]');

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            // Trimitem câmpurile existente către PHP.
            const response = await fetch("submit_booking.php", {
                method: "POST",
                body: new FormData(bookingForm)
            });

            if (!response.headers.get("content-type")?.includes("application/json")) {
                throw new Error(
                    "PHP did not return JSON. Check that submit_booking.php is next to index.html."
                );
            }

            const result = await response.json();

            if (!response.ok || result.success !== true) {
                throw new Error(
                    result.message || "The appointment request could not be saved."
                );
            }

            // Arătăm succesul NUMAI după confirmarea salvării în MySQL.
            if (bookingSuccess) {
                const heading = bookingSuccess.querySelector("strong");
                const description = bookingSuccess.querySelector("p");

                if (heading) {
                    heading.textContent = "Appointment request received.";
                }

                if (description) {
                    description.textContent =
                        "Your request has been saved. We will contact you to confirm it.";
                }

                bookingForm.insertAdjacentElement("afterend", bookingSuccess);
                bookingSuccess.classList.remove("d-none");
                bookingForm.classList.add("d-none");
                bookingModal?.querySelector(".modal-intro")?.classList.add("d-none");
            }

        } catch (error) {
            alert(
                "The request was not saved. " +
                (error.message || "Please try again.")
            );

        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
}



        /* =====================================================
           RESET FORM
        ===================================================== */

        if (
            bookingModal &&
            bookingForm
        ) {

            bookingModal.addEventListener(
                "hidden.bs.modal",
                function () {


                    bookingForm.reset();
                    bookingForm.classList.remove("d-none");
                        bookingModal?.querySelector(".modal-intro")?.classList.remove("d-none");


                    bookingForm.classList.remove(
                        "was-validated"
                    );


                    if (phoneInput) {

                        phoneInput.setCustomValidity(
                            ""
                        );

                    }


                    if (bookingSuccess) {

                        bookingSuccess.classList.add(
                            "d-none"
                        );

                    }

                }
            );

        }


    }
);