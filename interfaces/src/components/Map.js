import { MapContainer, Marker, Popup, TileLayer } from 'react-leaflet'
import L from 'leaflet';
import MapPopup from './MapPopup.js'

import 'leaflet/dist/leaflet.css'

delete L.Icon.Default.prototype._getIconUrl;

const Map = ({nodes, settings, loaded}) => {

  L.Icon.Default.mergeOptions({
    iconRetinaUrl: settings.clientPathToApp+'build/images/marker-icon-2x.png',
    iconUrl: settings.clientPathToApp+'build/images/marker-icon.png',
    shadowUrl: settings.clientPathToApp+'build/images/marker-shadow.png'
  });

  var loadingDiv;

  if(!loaded){
    loadingDiv = <div class="mri-map-loading"><img src={settings.clientPathToApp + "build/images/spinner.gif"} alt="loading..."/></div>
  }

  return (
    <div class="mri-map">
    {loadingDiv}
    <MapContainer center={settings.mapCenter} zoom={settings.mapZoom} scrollWheelZoom={settings.mapAllowScrollZoom} >
      <TileLayer
        attribution='&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
      />
      {nodes.map((node) => {
        if (node.data) {
          const id = node.id;
          node = node.data;
          node.id = id;
        }
        return(
          <div key={`${node.id || node.objectID}`}>
            {node.geolocation ?
            (<Marker position={[parseFloat(node.geolocation.lat), parseFloat(node.geolocation.lon)]}>
            <Popup>
              <MapPopup node={node} />
            </Popup>
          </Marker>):
          null}

            {(node.latitude && node.longitude)?
            (<Marker position={[parseFloat(node.latitude), parseFloat(node.longitude)]}>
            <Popup>
              <MapPopup node={node} />
            </Popup>
          </Marker>):
          null}

          </div>
          )
      })}
    </MapContainer>
    </div>
  )
}

export default Map
