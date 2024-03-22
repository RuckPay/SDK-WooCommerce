(() => {
    const e = window.ruckpay_payment_data,
    t = new function () {
        const t = this;
        this.options = {
            mode: e.settings.mode,
            public_key: "test" === e.settings.mode ? e.settings.test_key : e.settings.live_key,
            payment_button_id: "submit_payment_button",
            payment_cards_id: "ruckpay_iframe_area",
            method: "CARD",
            locale: e.locale,
            submitcallback: function (r) {
                if (r && r.transactionreference)
                    if (0 !== r.errorcode) {
                        let e = "An error has occurred, please retry later";
                        switch (r.errorcode) {
                        case 50003:
                            e = "Invalid payment data";
                            break;
                        case 60010:
                            e = "Internal error of the payment platform";
                            break;
                        case 7e4:
                            e = "Payment refused' mod='ruckpay"
                        }
                        t.showError(e + " (code : " + r.errorcode + ", message : " + r.errormessage + ")"),
                        t.toggleButtons(!0)
                    } else {
                        const t = document.createElement("form");
                        t.action = e.settings.checkout_page_url,
                        t.method = "post",
                        t.style.display = "none";
                        const n = document.createElement("input");
                        n.type = "hidden",
                        n.name = "external_reference",
                        n.value = r.transactionreference;
                        const o = document.createElement("input");
                        o.type = "hidden",
                        o.name = "payment_method",
                        o.value = "CARD",
                        t.appendChild(n),
                        t.appendChild(o),
                        document.body.appendChild(t),
                        t.submit()
                    }
                else
                    t.showError("Payment platform unreachable, please retry later"), t.toggleButtons(!0)
            },
            allowed_payment_methods: ["CARD"],
            styles: {
                payment: {
                    "space-outset-body": "0 4px 0 0",
                    "space-inset-input": "8px 16px",
                    "space-outset-input": "0",
                    "font-size-input": "14px",
                    "line-height-input": "20px",
                    "border-radius-input": "5px",
                    "border-color-input": "#ced4da",
                    "color-error": "#F44336",
                    "font-size-message": "0px"
                }
            },
            amount: e.order.amount,
            currency: e.order.currency,
            billing_contact: {
                title: "",
                first_name: e.order.billing.first_name,
                last_name: e.order.billing.last_name,
                countryiso: e.order.billing.country
            },
            billing_address: {
                street1: e.order.billing.address,
                zipcode: e.order.billing.zip,
                city: e.order.billing.city,
                Country: e.order.billing.country
            },
            customer_contact: {
                title: "",
                first_name: e.order.billing.first_name,
                last_name: e.order.billing.last_name,
                countryiso: e.order.billing.country
            },
            customer_address: {
                street1: e.order.billing.address,
                zipcode: e.order.billing.zip,
                city: e.order.billing.city,
                Country: e.order.billing.country
            },
            shipping_contact: {
                title: "",
                first_name: e.order.shipping.first_name,
                last_name: e.order.shipping.last_name,
                countryiso: e.order.shipping.country
            },
            shipping_address: {
                street1: e.order.shipping.address,
                zipcode: e.order.shipping.zip,
                city: e.order.shipping.city,
                Country: e.order.shipping.country
            },
            reference: e.order.id + ""
        },
        this.init = function () {
            t.ruckpaySubmitButton = document.getElementById("submit_payment_button"),
            t.ruckpay = new RuckPay(t.options),
            t.ruckpay.init("CARD"),
            t.ruckpaySubmitButton.addEventListener("click", (function (e) {
                    e.preventDefault(),
                    t.toggleButtons(!1),
                    t.hideError(),
                    t.options.storepaymentmethod = !1,
                    t.updateRuckPay()
                }))
        },
        this.updateRuckPay = function () {
            t.ruckpay.update(t.options.method)
        },
        this.showError = function (e) {
            const t = document.querySelector(".ruckpay-error");
            t.innerHTML = e,
            t.style.display = "block"
        },
        this.hideError = function () {
            const e = document.querySelector(".ruckpay-error");
            e.innerHTML = "",
            e.style.display = "none"
        },
        this.toggleButtons = function (e) {
            t.ruckpaySubmitButton.disabled = !e
        }
    };
    document.addEventListener("DOMContentLoaded", (function () {
            t.init()
        }))
})();
