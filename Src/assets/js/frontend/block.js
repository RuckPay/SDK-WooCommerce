(() => {
    "use strict";
    const e = window.React,
    t = window.wp.i18n,
    n = window.wc.wcBlocksRegistry,
    c = window.wp.htmlEntities,
    a = (0, window.wc.wcSettings.getSetting)("ruckpay_card_data"),
    i = (0, t.__)("RuckPay Payments", "woo-gutenberg-products-block"),
    o = (0, c.decodeEntities)(a.title) || i,
    r = () => (0, c.decodeEntities)(a.description || ""),
    s = {
        name: "ruckpay_card",
        label: (0, e.createElement)((t => {
                const {
                    PaymentMethodLabel: n
                } = t.components;
                return (0, e.createElement)(n, {
                    text: o
                })
            }), null),
        content: (0, e.createElement)(r, null),
        edit: (0, e.createElement)(r, null),
        canMakePayment: () => !0,
        ariaLabel: o,
        supports: {
            features: a.supports
        }
    };
    (0, n.registerPaymentMethod)(s)
})();
