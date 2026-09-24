// webpack.config.js
const path = require('path');
const sharedAliases = require('./webpack.aliases'); // Import your aliases
const externals = require('./webpack.externals');

module.exports = {
  mode: 'production',
  devtool: 'source-map',
  // You can define all your files as entry points here
  entry: {
    nonce: './nonce.js',
    select_picture: './select_picture.js',
    user_select: './user_select.js',
    logs: './logs.js',
    debug: './debug.js',
    table: './table.js',
    main: './main.js',
  },
  output: {
    module: true,
    path: path.resolve(__dirname, '.'),
    filename: '[name].min.js', // Automatically uses the entry key name (e.g., main.min.js)
  },
  resolve: {
    alias: {
        ...sharedAliases,
    },
  },
  experiments: {
    outputModule: true,
  },


  externalsType: 'module',
  externals
};