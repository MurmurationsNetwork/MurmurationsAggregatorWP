import React from 'react'
import PropTypes from 'prop-types'

function TableRow({ response, isSelected, onSelect }) {
  return (
    <tr>
      <td>
        <input
          type="checkbox"
          checked={isSelected}
          onChange={() => onSelect(response.id)}
        />
      </td>
      <td className="text-center">{response.id}</td>
      <td className="text-center">{response.name}</td>
    </tr>
  )
}

TableRow.propTypes = {
  response: PropTypes.object.isRequired,
  isSelected: PropTypes.bool.isRequired,
  onSelect: PropTypes.func.isRequired
}

export default TableRow
