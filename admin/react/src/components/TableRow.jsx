import React from 'react'
import PropTypes from 'prop-types'
import { getCustomNodes } from '../utils/api'

function TableRow({
  response,
  isSelected,
  onSelect,
  setIsPopupOpen,
  setOriginalJson,
  setModifiedJson
}) {
  const handleSeeUpdates = async (mapId, profileUrl, profileData) => {
    const response = await getCustomNodes(mapId, profileUrl)
    const responseData = await response.json()
    if (!response.ok) {
      alert(
        `Error fetching original profile with response: ${
          response.status
        } ${JSON.stringify(responseData)}`
      )
      return
    }

    setOriginalJson(responseData[0].profile_data)
    setModifiedJson(profileData)
    setIsPopupOpen(true)
  }

  return (
    <tr>
      <td className="text-center px-2 py-1">
        <input
          type="checkbox"
          checked={isSelected}
          onChange={() => onSelect(response.id)}
          disabled={!response.data.is_available}
        />
      </td>
      <td className="text-center px-2 py-1">{response.id}</td>
      <td className="text-center px-2 py-1">
        {response?.profile_data?.geolocation ? '📍' : ''}
      </td>
      <td className="text-center px-2 py-1">
        {response.profile_data.name
          ? response.profile_data.name
          : response.profile_data.title}
      </td>
      <td className="text-center underline">
        <a
          href={response.index_data.profile_url}
          target="_blank"
          rel="noreferrer"
          className="text-blue-500 underline"
        >
          {response.index_data.profile_url}
        </a>
      </td>
      <td className="text-center px-2 py-1">{response.data.status}</td>
      <td className="text-center px-2 py-1">
        {response.data.is_available
          ? 'Available'
          : 'Unavailable-' +
            (response.data.unavailable_message
              ? response.data.unavailable_message
              : '')}
      </td>
      <td className="text-center px-2 py-1">
        {response.data.extra_notes === 'see updates' ? (
          <button
            onClick={() =>
              handleSeeUpdates(
                response.data.map_id,
                response.index_data.profile_url,
                response.profile_data
              )
            }
            className="bg-orange-500 text-white font-bold animate-pulse px-2"
          >
            See Updates
          </button>
        ) : (
          response.data.extra_notes
        )}
      </td>
    </tr>
  )
}

TableRow.propTypes = {
  response: PropTypes.object.isRequired,
  isSelected: PropTypes.bool.isRequired,
  onSelect: PropTypes.func.isRequired,
  setIsPopupOpen: PropTypes.func.isRequired,
  setOriginalJson: PropTypes.func.isRequired,
  setModifiedJson: PropTypes.func.isRequired
}

export default TableRow
