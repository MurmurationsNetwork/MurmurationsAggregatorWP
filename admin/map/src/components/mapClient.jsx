import L from 'leaflet'
import MarkerClusterGroup from '@changey/react-leaflet-markercluster'

import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'
import { useEffect } from 'react'
import PropTypes from 'prop-types'
import {MapContainer, Marker, Popup, TileLayer} from "react-leaflet";

import 'leaflet/dist/leaflet.css'
import '@changey/react-leaflet-markercluster/dist/styles.min.css'

delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconUrl: iconUrl,
  iconRetinaUrl: iconRetinaUrl,
  shadowUrl: shadowUrl
})

const markerClicked = async postId => {
  console.log('markerClicked', postId)
}

function MapClient({ profiles, lat, lon, zoom }) {
  let defaultCenter = []
  defaultCenter[0] = parseFloat(lat) || 48.864716
  defaultCenter[1] = parseFloat(lon) || 2.349014
  let defaultZoom = parseInt(zoom) || 4

  useEffect(() => {
    (async function init() {
      delete L.Icon.Default.prototype._getIconUrl
    })()
  }, [])

  return (
    <MapContainer
      style={{ width: "100%", height: "24rem" }}
      center={defaultCenter}
      zoom={defaultZoom}
    >
      <TileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        attribution='&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
      />
      <MarkerClusterGroup>
        {profiles.map(profile => (
          <Marker
            key={profile[2]}
            position={[profile[1], profile[0]]}
            eventHandlers={{
              click: async event => {
                await markerClicked(profile[2])
                let popupInfo = event.target.getPopup()
                let content = ''
                popupInfo.setContent(content)
              }
            }}
          >
            <Popup></Popup>
          </Marker>
        ))}
      </MarkerClusterGroup>
    </MapContainer>
  )
}

MapClient.propTypes = {
  profiles: PropTypes.array.isRequired,
  lat: PropTypes.number.isRequired,
  lon: PropTypes.number.isRequired,
  zoom: PropTypes.number.isRequired
}

export default MapClient
