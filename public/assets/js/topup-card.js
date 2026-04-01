// Load history on page load
function loadHistory() {
  fetch("/api/topup/card/history", {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  })
    .then((res) => {
      return res.text().then((text) => {
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error("JSON parse error:", e);
          throw e;
        }
      });
    })
    .then((data) => {
      const tbody = document.getElementById("historyBody");

      if (!data || !data.ok || !data.data || data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999; padding: 20px;">Chưa có giao dịch nào</td></tr>';
        return;
      }

      let html = "";
      data.data.forEach((trans, idx) => {
        const statusMap = {
          0: '<span style="color: #ff9800;">⏳ Chờ xử lý</span>',
          1: '<span style="color: #4caf50;">✓ Thành công</span>',
          2: '<span style="color: #f44336;">✗ Thất bại</span>',
          3: '<span style="color: #ff9800;">⚠ Sai mệnh giá</span>',
        };

        const status = statusMap[trans.status] || trans.status;
        const time = new Date(trans.created_at).toLocaleString("vi-VN");

        html += `<tr>
          <td style="color: black !important">${idx + 1}</td>
          <td style="color: black !important">${trans.type}</td>
          <td style="color: black !important">${trans.seri}</td>
          <td style="color: black !important">${trans.pin}</td>
          <td style="color: black !important">${parseInt(trans.amount).toLocaleString("vi-VN")} ₫</td>
          <td style="color: black !important">${time}</td>
          <td>${status}</td>
        </tr>`;
      });

      tbody.innerHTML = html;
    })
    .catch((err) => {
      console.error("Error loading history:", err);
      document.getElementById("historyBody").innerHTML = '<tr><td colspan="7" style="text-align: center; color: #f44336; padding: 20px;">Lỗi tải lịch sử</td></tr>';
    });
}

// Load history on page load
document.addEventListener("DOMContentLoaded", loadHistory);

document.getElementById("cardForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  const msgDiv = document.getElementById("message");

  // Get values
  const cardType = formData.get("card_type");
  const amount = formData.get("amount");
  const serial = formData.get("serial");
  const pin = formData.get("pin");

  // Client-side validation
  const errors = [];
  if (!cardType) errors.push("Chọn loại thẻ");
  if (!amount) errors.push("Chọn mệnh giá");
  if (!serial) errors.push("Nhập số serial");
  if (serial && serial.length < 10) errors.push("Serial phải ít nhất 10 ký tự");
  if (!pin) errors.push("Nhập mã thẻ");
  if (pin && (pin.length < 8 || pin.length > 20)) errors.push("Mã thẻ phải từ 8-20 ký tự");

  if (errors.length > 0) {
    msgDiv.style.display = "block";
    msgDiv.className = "msg-error";
    msgDiv.innerHTML = errors.join(", ");
    return;
  }

  // Get username from page (must be set in HTML)
  const username = document.getElementById("username").value;

  const jsonData = {
    username: username,
    card_type: cardType,
    amount: parseInt(amount),
    serial: serial.trim(),
    pin: pin.trim(),
  };

  const btn = document.querySelector(".btn-change1");

  btn.disabled = true;
  btn.textContent = "⏳ Đang xử lý...";

  fetch("/api/topup/card", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(jsonData),
  })
    .then((res) => {
      return res.json().then((data) => ({
        status: res.status,
        data: data,
      }));
    })
    .then(({ status, data }) => {
      msgDiv.style.display = "block";

      if (data.ok) {
        msgDiv.className = "msg-success";
        msgDiv.innerHTML = "✓ Nạp thẻ thành công! Vui lòng chờ xử lý...";
        document.getElementById("cardForm").reset();

        // Reload history after 2 seconds
        setTimeout(function () {
          loadHistory();
          msgDiv.style.display = "none";
        }, 2000);
      } else {
        msgDiv.className = "msg-error";
        let errMsg = data.message || data.error || "Không rõ";
        if (data.title) {
          errMsg = data.title + ": " + errMsg;
        }
        if (data.details) {
          if (typeof data.details === "string") {
            errMsg += ": " + data.details;
          } else {
            errMsg += ": " + JSON.stringify(data.details);
          }
        }
        msgDiv.innerHTML = errMsg;
      }
    })
    .catch((err) => {
      msgDiv.style.display = "block";
      msgDiv.className = "msg-error";
      msgDiv.innerHTML = "✗ Lỗi kết nối: " + err;
      console.error("Fetch error:", err);
    })
    .finally(() => {
      btn.disabled = false;
      btn.textContent = "NẠP THẺ";
    });
});
