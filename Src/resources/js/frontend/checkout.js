const data = window.ruckpay_payment_data;

const RuckPayModule = function () {
    const _self = this;

    const callback = function (callbackData) {
        if (!callbackData || !callbackData.transactionreference) {
            _self.showError(
                "Payment platform unreachable, please retry later"
            );

            _self.toggleButtons(true);
        } else if (callbackData.errorcode !== 0) {
            let errorMessage = "An error has occurred, please retry later";

            switch (callbackData.errorcode) {
                case 50003:
                    errorMessage = "Invalid payment data";
                    break;
                case 60010:
                    errorMessage = "Internal error of the payment platform";
                    break;
                case 70000:
                    errorMessage = "Payment refused' mod='ruckpay";
                    break;
            }

            _self.showError(
                errorMessage + ' (code : ' + callbackData.errorcode + ', message : ' + callbackData.errormessage + ')'
            );

            _self.toggleButtons(true);
        } else {
            const form = document.createElement('form');
            form.action = data.settings.checkout_page_url;
            form.method = 'post';
            form.style.display = 'none';

            const externalReference = document.createElement('input');
            externalReference.type = 'hidden';
            externalReference.name = 'external_reference';
            externalReference.value = callbackData.transactionreference;

            const paymentMethod = document.createElement('input');
            paymentMethod.type = 'hidden';
            paymentMethod.name = 'payment_method';
            paymentMethod.value = 'CARD';
            form.appendChild(externalReference);
            form.appendChild(paymentMethod);
            document.body.appendChild(form);

            form.submit();
        }
    };

    this.options = {
        "mode": data.settings.mode,
        "public_key": data.settings.mode === 'test' ? data.settings.test_key : data.settings.live_key,

        "payment_button_id": "submit_payment_button",
        "payment_cards_id": "ruckpay_iframe_area",

        "method": "CARD",

        "locale": data.locale,
        "submitcallback": callback,

        "allowed_payment_methods": ["CARD"],

        "styles": {
            "payment": {
                "space-outset-body": "0 4px 0 0",
                "space-inset-input": "8px 16px",
                "space-outset-input": "0",
                "font-size-input": "14px",
                "line-height-input": "20px",
                "border-radius-input": "5px",
                "border-color-input": "#ced4da",
                "color-error": "#F44336",
                "font-size-message": "0px",
            },
        },

        "amount": data.order.amount,
        "currency": data.order.currency,

        "billing_contact": {
            "title": "",
            "first_name": data.order.billing.first_name,
            "last_name": data.order.billing.last_name,
            "countryiso": data.order.billing.country,
        },
        "billing_address": {
            "street1": data.order.billing.address,
            "zipcode": data.order.billing.zip,
            "city": data.order.billing.city,
            "Country": data.order.billing.country
        },
        "customer_contact": {
            "title": "",
            "first_name": data.order.billing.first_name,
            "last_name": data.order.billing.last_name,
            "countryiso": data.order.billing.country,
        },
        "customer_address": {
            "street1": data.order.billing.address,
            "zipcode": data.order.billing.zip,
            "city": data.order.billing.city,
            "Country": data.order.billing.country
        },
        "shipping_contact": {
            "title": "",
            "first_name": data.order.shipping.first_name,
            "last_name": data.order.shipping.last_name,
            "countryiso": data.order.shipping.country,
        },
        "shipping_address": {
            "street1": data.order.shipping.address,
            "zipcode": data.order.shipping.zip,
            "city": data.order.shipping.city,
            "Country": data.order.shipping.country
        },
        "reference": data.order.id + ''
    };

    this.init = function () {
        _self.ruckpaySubmitButton = document.getElementById('submit_payment_button');

        _self.ruckpay = new RuckPay(_self.options);
        _self.ruckpay.init('CARD');

        _self.ruckpaySubmitButton.addEventListener('click', function (e) {
            e.preventDefault();

            _self.toggleButtons(false);
            _self.hideError();
            _self.options.storepaymentmethod = false;
            _self.updateRuckPay();
        });
    }

    this.updateRuckPay = function () {
        _self.ruckpay.update(_self.options.method);
    }

    this.showError = function (message) {
        const errorElement = document.querySelector('.ruckpay-error');
        errorElement.innerHTML = message;
        errorElement.style.display = 'block';
    }

    this.hideError = function () {
        const errorElement = document.querySelector('.ruckpay-error');
        errorElement.innerHTML = '';
        errorElement.style.display = 'none';
    }

    this.toggleButtons = function (enable) {
        _self.ruckpaySubmitButton.disabled = !enable;
    }
};

const ruckpayModule = new RuckPayModule();

// on dom loaded
document.addEventListener('DOMContentLoaded', function () {
    ruckpayModule.init();
});