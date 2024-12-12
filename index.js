document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    const dialog = document.getElementById("addNotice");
    const messageContainer = document.createElement("div");

    messageContainer.className = "message-container";
    document.body.appendChild(messageContainer);

    form.addEventListener("submit", async (event) => {
        event.preventDefault(); // Prevent default form submission

        const formData = new FormData(form);

        try {
            const response = await fetch("submit_notice.php", {
                method: "POST",
                body: formData,
            });

            const result = await response.json();

            // Show success or error message
            messageContainer.textContent = result.message;
            messageContainer.style.color = result.status === "success" ? "green" : "red";

            if (result.status === "success") {
                form.reset(); // Reset form fields
                dialog.close(); // Close dialog
                fetchNotices(); // Refresh notices
            }
        } catch (error) {
            messageContainer.textContent = "An error occurred while submitting the notice.";
            messageContainer.style.color = "red";
        }
    });

    // Fetch notices and populate display section
    async function fetchNotices() {
        try {
            const response = await fetch("fetch_notices.php");
            const notices = await response.json();

            const display = document.getElementById("display");
            display.innerHTML = ""; // Clear existing notices

            notices.forEach((notice) => {
                const div = document.createElement("div");
                div.innerHTML = `
                    <p>${notice.content}</p>
                    <small>Importance: ${notice.importance_level}</small>
                `;
                display.appendChild(div);
            });
        } catch (error) {
            console.error("Error fetching notices:", error);
        }
    }

    fetchNotices(); // Fetch notices on page load
});
