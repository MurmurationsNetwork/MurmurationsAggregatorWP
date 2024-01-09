import L from 'leaflet'
import MarkerClusterGroup from '@changey/react-leaflet-markercluster'

import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'
import PropTypes from 'prop-types'
import { MapContainer, Marker, Popup, TileLayer } from 'react-leaflet'

import 'leaflet/dist/leaflet.css'
import '@changey/react-leaflet-markercluster/dist/styles.min.css'

delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconUrl: iconUrl,
  iconRetinaUrl: iconRetinaUrl,
  shadowUrl: shadowUrl
})

const getPost = async (postId, apiUrl) => {
  try {
    return await fetch(`${apiUrl}/api/wp-nodes/${postId}`)
  } catch (error) {
    alert(
      `Error getting post, please contact the administrator, error: ${error}`
    )
  }
}

const markerClicked = async (profile, apiUrl, linkType) => {
  // get profile data
  const response = await getPost(profile, apiUrl)
  const responseData = await response.json()
  if (response.status !== 200) {
    alert(
      `Error getting post, please contact the administrator, error: ${responseData}`
    )
  }
  let content = ''
  const schema = responseData?.profile_data?.linked_schemas[0]
  const imageUrl = responseData?.profile_data?.image
  switch (schema) {
    case 'organizations_schema-v1.0.0':
      content = addImageToContent(content, imageUrl)
      content = addTitleToContent(content, responseData?.profile_data?.name)
      content = addDescriptionToContent(
        content,
        responseData?.profile_data?.description
      )
      content = addUrlToContent(
        content,
        responseData?.profile_data?.primary_url,
        responseData.post_url,
        linkType
      )
      break
    case 'people_schema-v0.1.0':
      content = addImageToContent(content, imageUrl)
      content = addTitleToContent(
        content,
        responseData?.profile_data?.full_name
      )
      content = addDescriptionToContent(
        content,
        responseData?.profile_data?.description
      )
      content = addUrlToContent(
        content,
        responseData?.profile_data?.primary_url,
        responseData.post_url,
        linkType
      )
      break
    case 'offers_wants_schema-v0.1.0':
      content = addImageToContent(content, imageUrl)
      content = addTitleToContent(content, responseData?.profile_data?.title)
      content = addTextToContent(
        content,
        responseData?.profile_data?.exchange_type
      )
      content = addTextToContent(
        content,
        responseData?.profile_data?.transaction_type
      )
      content = addDescriptionToContent(
        content,
        responseData?.profile_data?.description
      )
      content = addUrlToContent(
        content,
        responseData?.profile_data?.details_url,
        responseData.post_url,
        linkType
      )
      break
  }

  return content
}

function addImageToContent(content, imageUrl) {
  if (imageUrl) {
    content += `<img src='${imageUrl}' style='height: 50px; width: auto' id='profile_image' alt="" />`

    const img = new Image()
    img.src = imageUrl
    img.onerror = () => {
      const profileImage = document.getElementById('profile_image')
      if (profileImage) {
        profileImage.style.display = 'none'
      }
    }
  }
  return content
}

function addTitleToContent(content, title) {
  if (title) {
    content += `<p><strong>${title}</strong></p>`
  }
  return content
}

function addTextToContent(content, text) {
  if (text) {
    content += `<p>${text}</p>`
  }
  return content
}

function addDescriptionToContent(content, description) {
  if (description) {
    content += `<p>${limitString(description, 200)}</p>`
  }
  return content
}

function addUrlToContent(content, primaryUrl, postUrl, linkType) {
  if (primaryUrl && linkType === 'primary') {
    content += `<p><a target='_blank' rel='noreferrer' href='${primaryUrl}'>${primaryUrl}</a></p>`
  }
  if (postUrl && linkType === 'wp') {
    content += `<p><a href='${postUrl}'>More...</a></p>`
  }
  return content
}

function limitString(inputString, maxLength) {
  if (inputString.length <= maxLength) {
    return inputString
  } else {
    return inputString.substring(0, maxLength) + '...'
  }
}

export default function MapClient({
  profiles,
  apiUrl,
  map,
  isMapLoaded,
  height,
  width,
  linkType
}) {
  let defaultCenter = []
  defaultCenter[0] = parseFloat(map.map_center_lat) || 48.86
  defaultCenter[1] = parseFloat(map.map_center_lon) || 2.34
  let zoom = parseInt(map.map_scale) || 5

  return (
    <div id="map">
      {isMapLoaded ? (
        <MapContainer
          style={{ height: `${height}vh`, width: `${width}vw` }}
          className="max-w-full"
          center={defaultCenter}
          zoom={zoom}
        >
          <TileLayer
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            attribution='&copy; <a target="_blank" rel="noreferrer" href="https://osm.org/copyright">OpenStreetMap</a> contributors'
          />
          <TileLayer
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            attribution='Data powered by <a target="_blank" rel="noreferrer" href="https://murmurations.network">Murmurations</a>'
          />
          <MarkerClusterGroup>
            {profiles.map(profile => (
              <Marker
                key={profile[2]}
                position={[profile[1], profile[0]]}
                eventHandlers={{
                  click: async event => {
                    // show loading while waiting for response
                    let popupInfo = event.target.getPopup()
                    popupInfo.setContent('loading from data source...')
                    const content = await markerClicked(
                      profile[2],
                      apiUrl,
                      linkType
                    )
                    popupInfo.setContent(content)
                  }
                }}
              >
                <Popup></Popup>
              </Marker>
            ))}
          </MarkerClusterGroup>
        </MapContainer>
      ) : null}
    </div>
  )
}

MapClient.propTypes = {
  profiles: PropTypes.array.isRequired,
  apiUrl: PropTypes.string.isRequired,
  map: PropTypes.object.isRequired,
  isMapLoaded: PropTypes.bool.isRequired,
  height: PropTypes.number.isRequired,
  width: PropTypes.number.isRequired,
  linkType: PropTypes.string.isRequired
}
