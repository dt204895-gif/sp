(() => {
  "use strict";

  // --- Webpack-like Module System ---
  // This structure simulates how tools like Webpack bundle modules.

  // Module definitions (simplified)
  var modules = {
    // Module 20: Provides a JSX factory function (similar to React.createElement)
    20: (module, exports, require) => {
      var React = require(609); // Requires the React module (609)
      var REACT_ELEMENT_TYPE = Symbol.for("react.element");
      var hasOwnProperty = Object.prototype.hasOwnProperty;
      var ReactCurrentOwner = React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner; // internal React mechanism to keep track of which component is currently rendering
      
      // Props that are reserved and shouldn't be copied directly
      var RESERVED_PROPS = {
        key: true,
        ref: true,
        __self: true,
        __source: true
      };

      // The actual JSX factory function
      exports.jsx = function(type, config, maybeKey) {
        var propName;
        var props = {};
        var key = null;
        var ref = null;

        // Handle key prop
        if (maybeKey !== undefined) {
          key = "" + maybeKey;
        }
        if (config.key !== undefined) {
          key = "" + config.key;
        }
        // Handle ref prop
        if (config.ref !== undefined) {
          ref = config.ref;
        }

        // Copy props from config, excluding reserved props
        for (propName in config) {
          if (hasOwnProperty.call(config, propName) && !RESERVED_PROPS.hasOwnProperty(propName)) {
            props[propName] = config[propName];
          }
        }

        // Apply default props if they exist
        if (type && type.defaultProps) {
          var defaultProps = type.defaultProps;
          for (propName in defaultProps) {
            if (props[propName] === undefined) {
              props[propName] = defaultProps[propName];
            }
          }
        }

        // Return the React element structure
        return {
          $$typeof: REACT_ELEMENT_TYPE,
          type: type,
          key: key,
          ref: ref,
          props: props,
          _owner: ReactCurrentOwner.current,
        };
      };
    },

    // Module 609: Exports the global React object
    609: (module) => {
      module.exports = window.React;
    },

    // Module 848: Re-exports the JSX factory from module 20
    848: (module, exports, require) => {
      module.exports = require(20); // Exports the content of module 20 (the jsx function)
    },
  };

  // Module cache
  var moduleCache = {};

  // The 'require' function to load modules
  function requireModule(moduleId) {
    var cachedModule = moduleCache[moduleId];
    if (cachedModule !== undefined) {
      return cachedModule.exports;
    }
    var module = moduleCache[moduleId] = { exports: {} };
    modules[moduleId](module, module.exports, requireModule); // Execute the module definition
    return module.exports;
  }

  // --- Main Application Logic ---

  // Import WordPress and WooCommerce global functions/objects
  const { __ } = window.wp.i18n; // Internationalization function
  const { registerPaymentMethod } = window.wc.wcBlocksRegistry; // Function to register payment methods for blocks
  const { decodeEntities } = window.wp.htmlEntities; // Function to decode HTML entities
  const { getSetting } = window.wc.wcSettings; // Function to get WooCommerce settings

  // Load the JSX factory function using the internal require
  const { jsx } = requireModule(848);

  // Get settings specific to this payment method ('sellapp')
  const sellappSettings = getSetting("sellapp_data", {}); // Default to empty object if not found

  // Define the default title, translatable
  const defaultTitle = __("SellApp", "woo-gutenberg-products-block");

  // Determine the actual title: use settings value if present, otherwise use default
  const paymentMethodTitle = decodeEntities(sellappSettings.title) || defaultTitle;

  // Function component to get the description (decodes entities)
  const PaymentMethodDescription = () => {
    return decodeEntities(sellappSettings.description || ""); // Use description from settings or empty string
  };

  // Component to render the payment method label
  // It expects 'components' prop to be passed by the block editor environment
  const PaymentMethodLabelComponent = (props) => {
    // The original code destructured 'PaymentMethodLabel' from the first argument 'e'.
    // In the context of WooCommerce Blocks, this usually comes from props.components.
    const { PaymentMethodLabel } = props.components;
    return jsx(PaymentMethodLabel, { text: paymentMethodTitle }); // Use the JSX factory
  };

  // Configuration object for the payment method
  const sellappPaymentMethodConfig = {
    name: "sellapp", // Unique identifier for the payment method
    label: jsx(PaymentMethodLabelComponent, {}), // Component for the label in the checkout block
    content: jsx(PaymentMethodDescription, {}), // Component for the content/description
    edit: jsx(PaymentMethodDescription, {}), // Component for the editor interface
    canMakePayment: () => true, // Logic to determine if the method is available (here, always true)
    ariaLabel: paymentMethodTitle, // Accessibility label
    supports: {
      features: sellappSettings.supports, // Declare supported features based on settings
    },
  };

  // Register the payment method with WooCommerce Blocks
  registerPaymentMethod(sellappPaymentMethodConfig);

})();