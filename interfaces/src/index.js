import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import MurmurationsInterface from './components/MurmurationsInterface.js';

const reactDirectory = document.getElementById('murmurations-react-directory');

const reactMap = document.getElementById('murmurations-react-map');

if (reactDirectory) {
  const settings = window.wpReactSettings;
  ReactDOM.render(<MurmurationsInterface settings={settings} interfaceComp="directory"/>, reactDirectory);
}

if (reactMap) {
  const settings = window.wpReactSettings;
  ReactDOM.render(<MurmurationsInterface settings={settings} interfaceComp="map" />, reactMap);
}
